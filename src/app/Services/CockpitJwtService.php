<?php

namespace App\Services;

use Exception;

class CockpitJwtService
{
    private $secretKey;
    private $algorithm = 'HS256';
    private $tokenExpiry = 3600; // 1 heure en secondes

    public function __construct()
    {
        $this->secretKey = getenv('JWT_SECRET_KEY') ?: 'EHA_JWT_DEFAULT_SECRET_2025';
    }

    /**
     * Génère un token JWT
     */
    public function generateToken($userId, $userNni, $userFullName = null)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        $payload = json_encode([
            'iss' => 'EHA_COCKPIT_API',
            'sub' => $userId,
            'user_nni' => $userNni,
            'user_full_name' => $userFullName,
            'iat' => time(),
            'exp' => time() + $this->tokenExpiry
        ]);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secretKey, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    /**
     * Valide et décode un token JWT
     */
    public function validateToken($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            list($base64Header, $base64Payload, $base64Signature) = $parts;

            // Vérifier la signature
            $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secretKey, true);
            $expectedSignature = $this->base64UrlEncode($signature);

            if (!hash_equals($base64Signature, $expectedSignature)) {
                return false;
            }

            // Décoder le payload
            $payload = json_decode($this->base64UrlDecode($base64Payload), true);

            // Vérifier l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }

            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Extrait le token du header Authorization
     */
    public function extractTokenFromHeader()
    {
        $headers = getallheaders();
        if (!$headers) {
            return null;
        }

        // Chercher le header Authorization (insensible à la casse)
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                if (preg_match('/Bearer\s+(.*)$/i', $value, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}