<?php

class ShareHash {

    private static string $key = "8f4d91e2c6a7b83f9d5e1a2b7c8d9e0f3a6b5c7d8e9f1029384756abcdef1234";

    public static function encode(int $id): string {
        return rtrim(
            strtr(
                base64_encode(
                    openssl_encrypt(
                        $id,
                        "AES-128-ECB",
                        self::$key
                    )
                ),
                '+/',
                '-_'
            ),
            '='
        );
    }


    public static function decode(string $hash): ?int {

        $decoded = openssl_decrypt(
            base64_decode(
                strtr($hash, '-_', '+/')
            ),
            "AES-128-ECB",
            self::$key
        );

        return $decoded === false ? null : intval($decoded);
    }
}