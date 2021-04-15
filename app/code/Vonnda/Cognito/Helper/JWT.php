<?php
namespace Vonnda\Cognito\Helper;

/**
 * Copied some functions from the git repo below. With some modifications.
 * @see https://github.com/firebase/php-jwt
 * @see http://codecipher.in/php-jwt/
 * @package Vonnda\Cognito\Helper
 */
class JWT
{

    public static function verify($jwt, $jwks)
    {
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $jwt);
        $header = json_decode(self::urlsafeB64Decode($headerEncoded));
        $publicKey = self::parseKeySet($jwks, $header->kid);
        $result = openssl_verify("$headerEncoded.$payloadEncoded", self::urlsafeB64Decode($signatureEncoded), $publicKey, 'sha256');
        if ($result === -1) {
            throw new \RuntimeException('Failed to verify signature: '.implode("\n", self::getOpenSSLErrors()));
        }
        return (bool)$result;
    }

    private static function getOpenSSLErrors()
    {
        $messages = [];
        while ($msg = openssl_error_string()) {
            $messages[] = $msg;
        }
        return $messages;
    }

    /**
     * Parse a set of JWK keys
     * @param object $jwks
     * @param string $kid id of the key used to encode the signature
     * @return array an associative array represents the set of keys
     */
    private static function parseKeySet($jwks, $kid)
    {
        foreach ($jwks->keys as $key) {
            if ($key->kid === $kid) {
                return self::parseKey($key);
            }
        }
        throw new \UnexpectedValueException('Failed to parse JWK');
    }

    /**
     * Parse a JWK key
     * @param $jwk
     * @return resource|array
     */
    private static function parseKey($jwk)
    {
        if (!is_array($jwk)) {
            $jwk = (array)$jwk;
        }
        if (!empty($jwk) && isset($jwk['kty']) && isset($jwk['n']) && isset($jwk['e']) && $jwk['kty'] === 'RSA') { // currently only handling RSA
            if (array_key_exists('d', $jwk)) {
                throw new \UnexpectedValueException('Failed to parse JWK: RSA private key is not supported');
            }
            $pem = self::createPemFromModulusAndExponent($jwk['n'], $jwk['e']);
            $pKey = openssl_pkey_get_public($pem);
            if ($pKey !== false) {
                return $pKey;
            }
        }
        throw new \UnexpectedValueException('Failed to parse JWK');
    }

    /**
     * Create a public key represented in PEM format from RSA modulus and exponent information
     * @param string $n the RSA modulus encoded in Base64
     * @param string $e the RSA exponent encoded in Base64
     * @return string the RSA public key represented in PEM format
     */
    private static function createPemFromModulusAndExponent($n, $e)
    {
        $modulus = self::urlsafeB64Decode($n);
        $publicExponent = self::urlsafeB64Decode($e);
        $components = array(
            'modulus' => pack('Ca*a*', 2, self::encodeLength(strlen($modulus)), $modulus),
            'publicExponent' => pack('Ca*a*', 2, self::encodeLength(strlen($publicExponent)), $publicExponent)
        );
        $RSAPublicKey = pack(
            'Ca*a*a*',
            48,
            self::encodeLength(strlen($components['modulus']) + strlen($components['publicExponent'])),
            $components['modulus'],
            $components['publicExponent']
        );
        // sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
        $rsaOID = pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
        $RSAPublicKey = chr(0) . $RSAPublicKey;
        $RSAPublicKey = chr(3) . self::encodeLength(strlen($RSAPublicKey)) . $RSAPublicKey;
        $RSAPublicKey = pack(
            'Ca*a*',
            48,
            self::encodeLength(strlen($rsaOID . $RSAPublicKey)),
            $rsaOID . $RSAPublicKey
        );
        $RSAPublicKey = "-----BEGIN PUBLIC KEY-----\r\n" . chunk_split(base64_encode($RSAPublicKey), 64) . '-----END PUBLIC KEY-----';
        return $RSAPublicKey;
    }

    /**
     * Decode a string with URL-safe Base64.
     * @param string $input A Base64 encoded string
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     * @param string $input The string you want encoded
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * DER-encode the length
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.
     * See {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 paragraph 8.1.3} for more information.
     * @access private
     * @param int $length
     * @return string
     */
    private static function encodeLength($length)
    {
        if ($length <= 0x7F) {
            return chr($length);
        }
        $temp = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }

}