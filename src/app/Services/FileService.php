<?php

namespace App\Services;
use Core\Http\Request;

/**
 * Service de gestion des fichiers
 * @deprecated Utiliser SecureFileService::secureDownload() à la place
 */
class FileService
{
    /**
     * Télécharge un fichier de manière sécurisée
     * 
     * @param array $options Options contenant 'file' et optionnellement 'directory'
     * @return bool|int
     */
    public static function download($options)
    {
        try {
            return SecureFileService::secureDownload($options);
        } catch (\Exception $e) {
            // Logger la tentative suspecte
            $userNni = $_SESSION['user_nni'] ?? 'unknown';
            SecureFileService::logSuspiciousAccess(
                $options['file'] ?? 'unknown',
                $userNni,
                $e->getMessage()
            );
            
            // Retourner une erreur générique (ne pas exposer les détails)
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Accès non autorisé',
                'code' => 403
            ]);
            exit;
        }
    }
}
