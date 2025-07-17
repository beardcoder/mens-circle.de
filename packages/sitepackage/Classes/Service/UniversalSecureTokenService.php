<?php

namespace MensCircle\Sitepackage\Service;

readonly class UniversalSecureTokenService
{
    protected string $encryptionKey;

    public function __construct()
    {
        $systemKey = (string) $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        $this->encryptionKey = hash('sha256', $systemKey, true);
    }

    public function encrypt(array $data, string $additionalData = ''): string
    {
        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $nonce = random_bytes($nonceLength);

        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            json_encode($data),
            $additionalData,
            $nonce,
            $this->encryptionKey,
        );

        $encrypted = $nonce . $ciphertext;

        return sodium_bin2base64($encrypted, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    public function decrypt(string $encodedData, string $additionalData = ''): array
    {
        $decoded = sodium_base642bin($encodedData, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);

        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $nonce = substr($decoded, 0, $nonceLength);
        $ciphertext = substr($decoded, $nonceLength);

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            $additionalData,
            $nonce,
            $this->encryptionKey,
        );

        if ($plaintext === false) {
            throw new \RuntimeException(
                'Die Entschlüsselung ist fehlgeschlagen – ungültige Authentifizierung.',
                3859513393,
            );
        }

        return json_decode($plaintext, true);
    }
}
