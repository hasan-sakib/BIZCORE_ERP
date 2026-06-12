<?php

declare(strict_types=1);

namespace App\Security;

class Encryptor
{
    private const CIPHER = 'AES-256-CBC';

    public function __construct(private readonly string $key) {}

    public function encrypt(string $data): string
    {
        $iv        = random_bytes(16);
        $encrypted = openssl_encrypt($data, self::CIPHER, $this->key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $data): string
    {
        $decoded   = base64_decode($data);
        $iv        = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        return (string) openssl_decrypt($encrypted, self::CIPHER, $this->key, 0, $iv);
    }
}
