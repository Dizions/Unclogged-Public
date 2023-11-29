<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Security\AccessControlList */
class AccessControlListTest extends TestCase
{
    public function testCanBeInstantiatedWithNoArgumentsToGetEmptyAcl(): void
    {
        $acl = new AccessControlList();
        $this->assertInstanceOf(AccessControlList::class, $acl);
        $this->assertJsonStringEqualsJsonString(AccessControlList::EMPTY_ACL, $acl->toJson());
    }

    /** @dataProvider validAclJsonProvider */
    public function testValidAclsPassValidation(string $json): void
    {
        $this->assertInstanceOf(AccessControlList::class, new AccessControlList($json));
    }

    /** @dataProvider validAclJsonProvider */
    public function testAclsCanBeCorrectlyConvertedBackToJsonAfterParsing(string $json, string $normalised = null): void
    {
        $this->assertSame($this->normaliseJson($normalised ?? $json), (new AccessControlList($json))->toJson());
    }

    /** @dataProvider invalidAclJsonProvider */
    public function testInvalidAclsFailValidation(string $json): void
    {
        $this->expectException(InvalidAclJsonException::class);
        new AccessControlList($json);
    }

    /** @dataProvider permissionResultProvider */
    public function testExpectedActionsArePermitted(string $json, array $permitted, array $denied): void
    {
        $acl = new AccessControlList($json);
        foreach ($permitted as $action) {
            $this->assertTrue($acl->isActionPermitted(...$action));
        }
        foreach ($denied as $action) {
            $this->assertFalse($acl->isActionPermitted(...$action));
        }
    }

    /** @dataProvider permissionResultProvider */
    public function testAllActionsAreDeniedWhenClientIdDoesntMatch(
        string $json,
        array $normallyPermitted,
        array $denied
    ): void {
        $acl = new AccessControlList($json);
        $acl->setScope(['clientId' => 1]);
        foreach ($normallyPermitted as $action) {
            $acl->setContext(['clientId' => null]);
            $this->assertFalse($acl->isActionPermitted(...$action));
            $acl->setContext(['clientId' => 1]);
            $this->assertTrue($acl->isActionPermitted(...$action));
        }
        foreach ($denied as $action) {
            $this->assertFalse($acl->isActionPermitted(...$action));
        }
    }

    public function testScopeCanBeAltered(): void
    {
        $acl = new AccessControlList();
        $acl->setScope(['clientId' => 1]);
        $this->assertSame(['clientId' => 1], $acl->getScope());
        $acl->setScopeValue('userId', 2);
        $this->assertSame(['clientId' => 1, 'userId' => 2], $acl->getScope());
    }

    public function testScopeCanBeReplaced(): void
    {
        $acl = new AccessControlList();
        $acl->setScope(['clientId' => 1]);
        $this->assertSame(1, $acl->getScopeValue('clientId'));
        $acl->setScope(['userId' => 2]);
        $this->assertSame(['userId' => 2], $acl->getScope());
        $this->assertSame(null, $acl->getScopeValue('clientId'));
    }

    public static function validAclJsonProvider(): array
    {
        return [
            [AccessControlList::EMPTY_ACL],
            ['{"version": 1, "allow": "*"}'],
            ['{"version": 1, "allow": "*", "scope": {"clientId": 1}}'],
            ['{"version": 1, "allow": [{"service": "*", "action": "*"}]}', '{"version": 1, "allow": "*"}'],
            [
                '{"version": 1, "allow": [{"service": "*", "action": "*"}, {"service": "foo", "action": "bar"}]}',
                '{"version": 1, "allow": "*"}',
            ],
            ['{"version": 1, "allow": [{"service": "foo", "action": "*", "resources": "*"}]}'],
            ['{"version": 1, "allow": [{"service": "foo", "action": "*", "resources": [{"type": "key", "id": 1}]}]}'],
            ['{"version": 1, "allow": [{"service": "foo", "action": "*", "resources": [{"type": "key", "id": "*"}]}]}'],
            ['{"version": 1, "allow": [{"service": "foo", "action": "*", "resources": [{"type": "key", "id": [1, 2]}]}]}'],
        ];
    }

    public static function invalidAclJsonProvider(): array
    {
        return [
            [''],
            ['{}'],
            ['{"version": 1}'],
            ['{"version": 1, "allow": "*", "scope": {}}'],
            ['{"version": 1, "allow": [{"action": "*"}]}'],
            ['{"version": 1, "allow": [{"service": "*"}]}'],
            ['{"version": 1, "allow": [{"service": "foo", "action": "*", "resources": []}]}'],
            ['{"version": 1, "allow": [{"service": "foo", "action": "*", "resources": [{"type": "foo"}]}]}'],
            ['{"version": 1, "allow": [{"service": "foo", "action": "*", "resources": [{"id": 1}]}]}'],
        ];
    }

    public static function permissionResultProvider(): array
    {
        $getKey1 = ['foo', 'GetKey', 'key', '1'];
        $getKey2 = ['foo', 'GetKey', 'key', '2'];
        $getKey99 = ['foo', 'GetKey', 'key', '99'];
        $deleteKey1 = ['foo', 'DeleteKey', 'key', '1'];
        $deleteThing1 = ['foo', 'DeleteThing', 'thing', '23'];
        $deleteThingThatIsAKey1 = ['foo', 'DeleteThing', 'key', '23'];
        $getThing1 = ['foo', 'GetThing', 'thing', '1'];
        $getThing99 = ['foo', 'GetThing', 'thing', '99'];
        $getThingThatIsAKey1 = ['foo', 'GetThing', 'key', '1'];
        $getThingThatIsAKey99 = ['foo', 'GetThing', 'key', '99'];
        $doTheThing = ['foo', 'DoTheThing'];
        $doTheOtherThing = ['bar', 'DoTheThing'];

        $allActions = [
            $getKey1,
            $getKey2,
            $deleteKey1,
            $deleteThing1,
            $deleteThingThatIsAKey1,
            $getKey99,
            $getThing1,
            $getThing99,
            $getThingThatIsAKey1,
            $getThingThatIsAKey99,
            $doTheThing,
            $doTheOtherThing
        ];
        return [
            [json_encode(['version' => 1, 'allow' => '*']), $allActions, []],
            [json_encode(['version' => 1, 'allow' => '*', 'scope' => ['clientId' => 1]]), [], $allActions],
            [
                json_encode([
                    'version' => 1,
                    'allow' => [
                        ['service' => 'foo', 'action' => 'Get*', 'resources' => [['type' => '*', 'id' => 1]]],
                        ['service' => 'foo', 'action' => 'GetKey', 'resources' => [['type' => 'key', 'id' => 99]]],
                        ['service' => 'foo', 'action' => 'GetThing', 'resources' => [['type' => 'thing', 'id' => '*']]],
                        ['service' => 'foo', 'action' => 'DeleteThing', 'resources' => [['type' => 'key', 'id' => 23]]],
                        ['service' => 'foo', 'action' => 'DoTheThing'],
                    ]
                ]),
                [$getKey1, $getKey99, $getThing1, $getThing99, $getThingThatIsAKey1, $doTheThing, $deleteThingThatIsAKey1],
                [$getKey2, $getThingThatIsAKey99, $doTheOtherThing, $deleteKey1, $deleteThing1]
            ],
        ];
    }
}
