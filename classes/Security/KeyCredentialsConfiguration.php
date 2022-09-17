<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\Database\Schema\{ColumnSchema, ColumnType, TableSchema};
use Dizions\Unclogged\Security\Password\PasswordValidator;
use Dizions\Unclogged\Setup\Environment;

class KeyCredentialsConfiguration
{
    private Environment $env;
    private string $applicationName;
    private PasswordValidator $passwordValidator;
    private array $memo;

    public function __construct(Environment $env, string $applicationName, PasswordValidator $validator)
    {
        $this->env = $env;
        $this->applicationName = $applicationName;
        $this->passwordValidator = $validator;
    }

    public function getPasswordValidator(): PasswordValidator
    {
        return $this->passwordValidator;
    }

    /**
     * Get the scheme to use for a caller to authenticate with the `Authorization` header, as an
     * alternative to authenticating with a dedicated header.
     * @return string
     */
    public function getAuthenticationScheme(): string
    {
        return $this->memo[__METHOD__] ??= implode('', $this->getApplicationNameUcWords()) . 'Key';
    }

    /**
     * Get the header to check for a caller's API key.
     * @return string
     */
    public function getKeyHeader(): string
    {
        return $this->memo[__METHOD__] ??= implode('-', $this->getApplicationNameUcWords()) . '-Key';
    }

    public function getKeyTable(): string
    {
        return $this->memo[__METHOD__] ??= $this->getTableNamePrefix() . 'keys';
    }

    public function getKeyTableSchema(): TableSchema
    {
        return (new TableSchema($this->getKeyTable(), [
            ColumnSchema::new('key_id')
                ->setType(ColumnType::int()->setUnsigned())
                ->setAutoIncrement(true)
                ->setComment('Surrogate key to make references more efficient'),
            ColumnSchema::char('key_key', 12, 'ascii'),
            ColumnSchema::varchar('key_secret_hash', 255, 'ascii'),
            ColumnSchema::datetime('key_valid_after')->setDefault('CURRENT_TIMESTAMP'),
            ColumnSchema::datetime('key_valid_before')->setNullable(),
            ColumnSchema::bit('key_is_ephemeral', 1)
                ->setDefault('false')
                ->setComment('Ephemeral keys cannot be changed and are automatically deleted after expiration'),
            ColumnSchema::text('key_description', 'utf8mb4'),
            ColumnSchema::datetime('key_last_used')->setNullable()->setDefault('NULL'),
            ColumnSchema::json('key_restrict_to_ip_addresses')->setNullable()->setDefault('NULL'),
            ColumnSchema::json('key_acl')->setDefault("'" . AccessControlList::EMPTY_ACL . "'"),
        ]))->setPrimary(['key_id'])
           ->addUnique(['key_key'], 'key_unq')
           // To support 'DELETE FROM keys WHERE key_is_ephemeral = true AND key_valid_before < NOW()'
           ->addIndex(['key_is_ephemeral', 'key_valid_before'], 'ephemeral_idx');
    }

    private function createDefaultTableNamePrefix(): string
    {
        return $this->memo[__METHOD__] ??= implode('_', array_map(
            fn($word) => strtolower(preg_replace('/[^A-Za-z]/', '', $word)),
            $this->getApplicationNameWords()
        )) . '_';
    }

    /** @return string[] */
    private function getApplicationNameUcWords(): array
    {
        return $this->memo[__METHOD__] ??= array_map(
            fn($word) => ucwords(strtolower(preg_replace('/[^A-Za-z]/', '', $word))),
            $this->getApplicationNameWords()
        );
    }

    /** @return string[] */
    private function getApplicationNameWords(): array
    {
        return $this->memo[__METHOD__] ??= preg_split(
            '/(?=[A-Z][^-_A-Z\s])|[-_\s]+/',
            $this->applicationName,
            -1,
            PREG_SPLIT_NO_EMPTY
        );
    }

    private function getTableNamePrefix(): string
    {
        return $this->memo[__METHOD__] ??=
            $this->env->get('AUTHENTICATION_KEY_TABLE_NAME_PREFIX') ??
            $this->createDefaultTableNamePrefix();
    }
}
