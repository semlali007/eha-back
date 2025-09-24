<?php

namespace App\Controllers;

class AuthController extends Controller
{
    public function authenticate()
    {
        // récupére les données
        $userInfo = json_decode(file_get_contents('php://input'), true);

        // vérification des champs requis
        $userInfoFields = ['gardianwebsso_groupid', 'gardianwebsso_id', 'gardianwebsso_cn', 'gardianwebsso_department', 'gardianwebsso_email', 'gardianwebsso_ismemberof', 'gardianwebsso_status'];

        foreach ($userInfoFields as $field) {
            if (!isset($userInfo[$field])) {
                // en cas d'absence
                echo json_encode(['error' => 'Le champ "' . $field . '" est requis'], 400);
                return; 
            }
        }

        // extraire les données des champs
        $nni =  $userInfo['gardianwebsso_id'][0];

        // appel de checkUserAuthorization pour l'authentification
        $this->checkUserAuthorization(['ROLE_GENERATE_TOKEN'], $userInfo['gardianwebsso_id'][0]);

        // Si vrai
        $accessToken = $this->generateAccessTokenForUser($nni);

        // Retourne le token
        echo json_encode(['access_token' => $accessToken]);
    }  
     
    /**
     * Génère un token JWT pour l'utilisateur
     */
    public function generateAccessTokenForUser($nni)
    {
        $secretKey = 'eB?7csn7Wo?+';

        // En-tête du token JWT
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        // Données à inclure dans le token
        $payload = json_encode([
            'nni' => $nni,
            'exp' => strtotime('+1 hour')
        ]);

        // Encodage
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $tokenBody = $base64UrlHeader . '.' . $base64UrlPayload;

        // Signer le token avec la clé secrète
        $signature = hash_hmac('sha256', $tokenBody, $secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $tokenBody . '.' . $base64UrlSignature;

        return $jwt;
    }
}
