<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use DateTime;
use DateTimeZone;
use PDO;
use PDOException;
use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\Request\IpAddressRange;
use Dizions\Unclogged\Request\Request;
use Dizions\Unclogged\Setup\InvalidConfigurationException;

/**
 * A key stored explicitly in the database, with associated configuration/metadata.
 *
 * An authentication key has a format like Identifier-Secret, where Identifier is a 32 character
 * string containing only upper and lowercase letters, and numbers.
 *
 * @package Dizions\Unclogged\Security
 */
class KeyCredentials implements CredentialsInterface
{
    private const KEY_ID_LENGTH = 12;
    private const KEY_SECRET_DEFAULT_LENGTH = 32;
    private const KEY_ID_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const SECRET_ALPHABET = self::KEY_ID_ALPHABET . '_:.,/?!@=-+*&^%';
    private const INVALID_KEY_ERROR = 'Invalid authentication key';
    private const INVALID_KEY_FORMAT_ERROR = 'Invalid authentication key format';

    private AccessControlList $acl;
    private KeyCredentialsConfiguration $config;
    private Database $database;
    private array $keyRecord;

    public function __construct(KeyCredentialsConfiguration $config, Database $database)
    {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     * @param Request $request
     * @return CredentialsInterface
     * @throws MissingCredentialsException
     * @throws InvalidCredentialsException
     * @throws InvalidConfigurationException
     */
    public function authenticate(Request $request): CredentialsInterface
    {
        $key = $this->getKeyFromRequest($request);
        [$keyId, $secret] = $this->splitKeyAndSecret($key);
        $keyRecord = $this->getKeyRecord($keyId);
        if (!$this->config->getPasswordValidator()->isPasswordCorrect($secret, $keyRecord['key_secret_hash'])) {
            throw new InvalidCredentialsException(self::INVALID_KEY_ERROR);
        }
        $this->updateHashIfNeeded($keyRecord, $secret);
        $this->validateAccessDateIsWithinRange(
            $keyRecord['key_valid_before'] ?? '',
            $keyRecord['key_valid_after'] ?? ''
        );
        if ($keyRecord['key_restrict_to_ip_addresses']) {
            $validAddressRanges = json_decode(
                $keyRecord['key_restrict_to_ip_addresses'],
                true,
                2,
                JSON_THROW_ON_ERROR
            );
            $this->validateIpAddressIsWithinRanges($request->getRemoteAddress(), $validAddressRanges);
        }
        $this->updateLastUsedDate($keyRecord);
        $this->keyRecord = $keyRecord;
        return $this;
    }

    /**
     * Generate a new string for use as an authentication key
     */
    public function createNewKey(): string
    {
        return $this->createNewKeyIdString() . '-' . $this->createNewSecret();
    }

    public function getAcl(): AccessControlList
    {
        if (!isset($this->keyRecord)) {
            throw new NotYetAuthenticatedException();
        }
        return $this->acl ??= new AccessControlList($this->keyRecord['key_acl']);
    }

    /**
     * Generate the identifier part of a new key
     * @return string
     */
    private function createNewKeyIdString(): string
    {
        return $this->config->getPasswordValidator()->generateRandomString(self::KEY_ID_LENGTH, self::KEY_ID_ALPHABET);
    }

    /**
     * Generate the secret part of a new key
     * @return string
     */
    private function createNewSecret(): string
    {
        return $this->config->getPasswordValidator()->generateRandomString(
            self::KEY_SECRET_DEFAULT_LENGTH,
            self::SECRET_ALPHABET
        );
    }

    private function getKeyFromAuthenticateHeader(Request $request): ?string
    {
        if (!$request->getServerRequest()->hasHeader('WWW-Authenticate')) {
            return null;
        }
        $headers = $request->getServerRequest()->getHeader('WWW-Authenticate');
        $header = end($headers);
        $parts = explode(' ', $header);
        if (count($parts) < 2 || $parts[0] != $this->config->getAuthenticationScheme()) {
            return null;
        }
        return $parts[1];
    }

    private function getKeyFromDedicatedHeader(Request $request): ?string
    {
        if (!$request->getServerRequest()->hasHeader($this->config->getKeyHeader())) {
            return null;
        }
        $headers = $request->getServerRequest()->getHeader($this->config->getKeyHeader());
        return end($headers);
    }

    private function getKeyFromRequest(Request $request): string
    {
        $key = $this->getKeyFromDedicatedHeader($request) ?? $this->getKeyFromAuthenticateHeader($request);
        if ($key === null) {
            throw new MissingCredentialsException();
        }
        return $key;
    }

    /**
     * @param string $key
     * @return array
     * @throws InvalidConfigurationException
     * @throws InvalidCredentialsException
     */
    private function getKeyRecord(string $key): array
    {
        try {
            $statement = $this->database->prepare(
                'SELECT
                key_id,
                key_secret_hash,
                key_valid_after,
                key_valid_before,
                key_restrict_to_ip_addresses,
                key_acl
                FROM ' . $this->config->getKeyTable() . '
                WHERE key_key = ?'
            );
            $statement->execute([$key]);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) == 0) {
                throw new InvalidCredentialsException(self::INVALID_KEY_ERROR);
            }
            return $rows[0];
        } catch (PDOException $e) {
            throw new InvalidConfigurationException('Failed to query for authentication key', 0, $e);
        }
    }

    private function isValidKeyIdString($key): bool
    {
        return strlen($key) == self::KEY_ID_LENGTH && !preg_match('/[^' . self::KEY_ID_ALPHABET . ']/', $key);
    }

    private function splitKeyAndSecret($fullKey): array
    {
        $parts = explode('-', $fullKey, 2);
        if (!$this->isValidKeyIdString($parts[0])) {
            throw new InvalidCredentialsException(self::INVALID_KEY_FORMAT_ERROR);
        }
        return $parts;
    }

    private function updateHashIfNeeded(array $keyRecord, string $secret): void
    {
        $validator = $this->config->getPasswordValidator();
        if (!$validator->isHashUpToDate($keyRecord['key_secret_hash'])) {
            $newHash = $validator->generatePasswordHash($secret);
            $keyTable = $this->config->getKeyTable();
            $update = $this->database->prepare("UPDATE $keyTable SET key_secret_hash = ? WHERE key_id = ?");
            $update->execute([$newHash, $keyRecord['key_id']]);
        }
    }

    private function updateLastUsedDate(array $keyRecord): void
    {
        $keyTable = $this->config->getKeyTable();
        $update = $this->database->prepare("UPDATE $keyTable SET key_last_used = CURRENT_TIMESTAMP WHERE key_id = ?");
        $update->execute([$keyRecord['key_id']]);
    }

    /** @throws InvalidCredentialsException */
    private function validateAccessDateIsWithinRange(string $before, string $after): void
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $after = new DateTime($after, new DateTimeZone('UTC'));
        if ($now < $after) {
            // '<' instead of '<=' because 'after' is only stored to seconds resolution, so any time
            // that matches as equal is actually after, even if only infinitesimally.
            throw new InvalidCredentialsException('Authentication key not yet valid');
        }
        if (!$before) {
            return;
        }
        $before = new DateTime($before, new DateTimeZone('UTC'));
        if ($now >= $before) {
            throw new InvalidCredentialsException('Authentication key has expired');
        }
    }

    /** @throws InvalidCredentialsException */
    private function validateIpAddressIsWithinRanges(string $address, array $ranges): void
    {
        foreach ($ranges as $range) {
            $ipAddressRange = new IpAddressRange($range);
            if ($ipAddressRange->contains($address)) {
                return;
            }
        }
        throw new InvalidCredentialsException('IP address not permitted');
    }
}
