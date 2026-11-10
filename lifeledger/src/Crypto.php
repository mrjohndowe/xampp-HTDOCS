<?php
declare(strict_types=1);

namespace LifeLedger;

final class Crypto
{
    public static function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    public static function encrypt(?string $plaintext, string $base64Key, string $table, string $recordId, string $field): ?string
    {
        if ($plaintext === null || $plaintext === '') return null;
        $key = base64_decode($base64Key, true);
        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException('Invalid 32-byte encryption key.');
        }
        $iv = random_bytes(12);
        $tag = '';
        $aad = $table . ':' . $recordId . ':' . $field;
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, 16);
        if ($cipher === false) {
            throw new \RuntimeException('Encryption failed.');
        }
        return 'v1:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($cipher);
    }

    public static function decrypt(?string $envelope, string $base64Key, string $table, string $recordId, string $field): ?string
    {
        if ($envelope === null || $envelope === '') return null;
        $parts = explode(':', $envelope);
        if (count($parts) !== 4 || $parts[0] !== 'v1') {
            throw new \RuntimeException('Invalid encrypted envelope.');
        }
        $key = base64_decode($base64Key, true);
        $iv = base64_decode($parts[1], true);
        $tag = base64_decode($parts[2], true);
        $cipher = base64_decode($parts[3], true);
        $aad = $table . ':' . $recordId . ':' . $field;
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        if ($plain === false) {
            throw new \RuntimeException('Decryption authentication failed.');
        }
        return $plain;
    }

    public static function assertNoCvv(array $payload): void
    {
        $banned = ['cvv', 'cvv2', 'cvc', 'cvc2', 'securitycode', 'security_code'];
        foreach ($payload as $k => $v) {
            if (in_array(strtolower((string)$k), $banned, true) && $v !== null && trim((string)$v) !== '') {
                throw new \RuntimeException('Security policy violation: CVV/CVC is strictly forbidden and never stored.');
            }
        }
    }

    public static function canonicalHash(array $row): string
    {
        ksort($row);
        $norm = [];
        foreach ($row as $k => $v) {
            if ($v === null) {
                $norm[$k] = null;
            } elseif (is_bool($v)) {
                $norm[$k] = $v ? 1 : 0;
            } elseif (is_int($v) || is_float($v)) {
                $norm[$k] = $v;
            } else {
                $norm[$k] = (string)$v;
            }
        }
        return hash('sha256', json_encode($norm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
