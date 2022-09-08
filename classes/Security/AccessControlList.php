<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use stdClass;

class AccessControlList
{
    public const EMPTY_ACL = '{"version": 1, "allow": []}';
    public const ALL_PERMISSIONS = '{"version": 1, "allow": "*"}';
    public const SCHEMA = <<<'END'
        {
            "$schema": "https://json-schema.org/draft/2020-12/schema",
            "definitions": {
                "types": {
                    "idList": {
                        "oneOf":[
                            {"const": "*"},
                            {"type": "integer"},
                            {"type": "array", "minItems": 1, "items": {"type": "integer"}}
                        ]
                    }
                },
                "resources": {
                    "oneOf": [
                        {"const": "*"},
                        {
                            "type": "array",
                            "minItems": 1,
                            "items": {
                                "type": "object",
                                "required": ["type", "id"],
                                "properties": {"type": {"type": "string"},
                                    "id": {"$ref": "#/definitions/types/idList"}
                                }
                            },
                            "uniqueItems": true
                        }
                    ]
                }
            },
            "type": "object",
            "required": ["version", "allow"],
            "properties": {
                "version": {"const": 1},
                "allow": {
                    "oneOf": [
                        {"const": "*"},
                        {
                            "type": "array",
                            "items": {
                                "type": "object",
                                "required": ["service", "action"],
                                "properties": {
                                    "service": {"type": "string"},
                                    "action": {"type": "string"},
                                    "resources": {"$ref": "#/definitions/resources"}
                                }
                            },
                            "uniqueItems": true
                        }
                    ]
                },
                "scope": {
                    "type": "object",
                    "propertyNames": {"type": "string"},
                    "minProperties": 1,
                    "additionalProperties": {"type": ["number", "string"]}
                }
            }
        }
        END;
    private array $allow = [];
    private bool $allowsEverything = false;
    private array $context = [];
    private array $scope = [];

    public function __construct(string $jsonAcl = self::EMPTY_ACL)
    {
        $acl = json_decode($jsonAcl);
        $this->validateAcl($acl);
        if ($acl->allow == '*') {
            $this->addAllowAce('*');
        } else {
            foreach ($acl->allow as $ace) {
                $this->addMinifiedAllowAce($ace);
            }
        }
        foreach ($acl->scope ?? [] as $key => $value) {
            $this->setScopeValue($key, $value);
        }
    }

    public function addAllowAce(
        string $service,
        string $action = '*',
        string $resourceType = '*',
        string $resourceId = '*'
    ): self {
        if ($this->allowsEverything) {
            return $this;
        }
        if ([$service, $action, $resourceType, $resourceId] === ['*', '*', '*', '*']) {
            $this->allow = [];
            $this->allowsEverything = true;
        }
        $this->allow[$service][$action][$resourceType][$resourceId] = true;
        return $this;
    }

    public function getScope(): array
    {
        return $this->scope;
    }

    /** @return int|float|string|null */
    public function getScopeValue(string $key)
    {
        return $this->scope[$key] ?? null;
    }

    public function isActionPermitted(
        string $service,
        string $action = '*',
        string $resourceType = '*',
        string $resourceId = '*'
    ): bool {
        if (!$this->doesContextMatchScope()) {
            return false;
        }
        if ($this->allowsEverything) {
            return true;
        }
        return $this->isActionPermittedByRules(
            $this->getMatchingRules($this->allow, $service),
            $action,
            $resourceType,
            $resourceId
        );
    }

    /**
     * Set the context for all subsequent permission checks. If the ACL has a scope defined,
     * permission will only be granted if every scope requirement has a matching value in the
     * context.
     *
     * @param array $context
     * @return static
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Limit the permissions granted by this ACL to apply only when the context matches the given
     * scope.
     * @param array<string, mixed> $scope
     * @return static
     */
    public function setScope(array $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Limit the permissions granted by this ACL to apply only when the context matches the given
     * scope.
     * @param array<string, string> $scope
     * @return static
     */
    public function setScopeValue(string $key, $value): self
    {
        $this->scope[$key] = $value;
        return $this;
    }

    public function toJson(): string
    {
        $allow = [];
        foreach ($this->allow as $service => $actionAce) {
            foreach ($actionAce as $action => $resources) {
                $allow[] = [
                    'service' => $service,
                    'action' => $action,
                    'resources' => $this->flattenResources($resources)
                ];
            }
        }
        return json_encode(['version' => 1, 'allow' => $this->minifyAllow($allow)]);
    }

    private function addMinifiedAllowAce(stdClass $ace): self
    {
        if (!isset($ace->resources) || $ace->resources == '*') {
            return $this->addAllowAce($ace->service, $ace->action);
        }
        foreach ($ace->resources as $resource) {
            $this->addMinifiedAllowAceForResource($ace->service, $ace->action, $resource);
        }
        return $this;
    }

    private function addMinifiedAllowAceForResource(string $service, string $action, stdClass $resource): self
    {
        foreach ((array)$resource->id as $id) {
            $this->addAllowAce($service, $action, $resource->type, (string)$id);
        }
        return $this;
    }

    private function doesContextMatchScope(): bool
    {
        foreach ($this->scope as $key => $value) {
            if (!array_key_exists($key, $this->context) || $this->context[$key] !== $value) {
                return false;
            }
        }
        return true;
    }

    private function flattenResources(array $in): array
    {
        $resources = [];
        foreach ($in as $type => $ids) {
            $ids = array_keys($ids);
            if (count($ids) == 1) {
                $ids = $ids[0];
            }
            $resources[] = ['type' => $type, 'id' => $ids];
        }
        return $resources;
    }

    private function getMatchingRules(array $rules, string $target): array
    {
        $ruleArrays = array_filter($rules, fn($k) => fnmatch($k, $target), ARRAY_FILTER_USE_KEY);
        // We can't use array_merge() because it renumbers integer keys
        $merged = [];
        foreach ($ruleArrays as $ruleArray) {
            $merged += $ruleArray;
        }
        return $merged;
    }

    private function isActionPermittedByRules(
        array $rules,
        string $action = '*',
        string $resourceType = '*',
        string $resourceId = '*'
    ): bool {
        if (empty($rules)) {
            return false;
        }
        return $this->isResourceTypePermittedByRules(
            $this->getMatchingRules($rules, $action),
            $resourceType,
            $resourceId
        );
    }

    private function isResourceTypePermittedByRules(
        array $rules,
        string $resourceType = '*',
        string $resourceId = '*'
    ): bool {
        if (empty($rules)) {
            return false;
        }
        return $this->isResourcePermittedByRules($this->getMatchingRules($rules, $resourceType), $resourceId);
    }

    private function isResourcePermittedByRules(array $rules, string $resourceId = '*'): bool
    {
        if (empty($rules)) {
            return false;
        }
        return !empty(array_filter(
            $rules,
            fn($v, $k) => $v === true && ($k === '*' || $k == $resourceId),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    /** @return string|array */
    private function minifyAllow(array $in)
    {
        $out = [];
        foreach ($in as $ace) {
            if ($ace['resources'] == [['type' => '*', 'id' => '*']]) {
                $ace['resources'] = '*';
            }
            $out[] = $ace;
        }
        if ($out == [['service' => '*', 'action' => '*', 'resources' => '*']]) {
            $out = '*';
        }
        return $out;
    }

    /** @param stdClass|string $acl */
    private function validateAcl($acl): void
    {
        $validator = new Validator();
        $validation = $validator->validate($acl, self::SCHEMA);
        if (!$validation->isValid()) {
            $errors = (new ErrorFormatter())->format($validation->error());
            // The last error is typically the useful one as previous errors are likely to be about
            // the "*" alternatives that weren't taken.
            $lastErrorPath = array_key_last($errors);
            $lastError = end($errors[$lastErrorPath]);
            throw new InvalidAclJsonException("$lastErrorPath: $lastError");
        }
    }
}
