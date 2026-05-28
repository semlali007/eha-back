<?php

namespace App\Services;

use Exception;

/**
 * Service de gestion sécurisée des fichiers
 * Protection contre Path Traversal et accès non autorisés
 */
class SecureFileService
{
    /**
     * Dossiers autorisés pour le téléchargement
     * Clé = identifiant, Valeur = chemin absolu
     */
    private static $allowedDirectories = [
        'uploads' => null,      // Sera initialisé avec CONFIG_UPLOAD_PATH
        'factsheets' => null,   // Pour les fiches de données
        'hotline' => null,      // Pour les pièces jointes hotline
    ];

    /**
     * Extensions de fichiers autorisées
     */
    private static $allowedExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 
        'ppt', 'pptx', 'txt', 'csv',
        'jpg', 'jpeg', 'png', 'gif',
        'zip', 'rar'
    ];

    /**
     * Initialise les chemins des dossiers autorisés
     */
    private static function initializeDirectories(): void
    {
        if (self::$allowedDirectories['uploads'] === null) {
            self::$allowedDirectories['uploads'] = realpath(getenv("CONFIG_UPLOAD_PATH")) ?: getenv("CONFIG_UPLOAD_PATH");
            
            // Ajouter d'autres dossiers si nécessaire
            $basePath = dirname(self::$allowedDirectories['uploads']);
            self::$allowedDirectories['factsheets'] = $basePath . DIRECTORY_SEPARATOR . 'factsheets';
            self::$allowedDirectories['hotline'] = $basePath . DIRECTORY_SEPARATOR . 'hotline';
        }
    }

    /**
     * Nettoie et valide un nom de fichier
     * Supprime les caractères dangereux et les tentatives de Path Traversal
     * 
     * @param string $filename Nom du fichier à nettoyer
     * @return string Nom de fichier sécurisé
     * @throws Exception Si le nom contient des caractères interdits
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Décoder une seule fois pour éviter le double encoding
        $filename = urldecode($filename);
        
        // Supprimer les null bytes
        $filename = str_replace(chr(0), '', $filename);
        
        // Détecter les tentatives de Path Traversal
        if (
            strpos($filename, '..') !== false ||
            strpos($filename, './') !== false ||
            strpos($filename, '.\\') !== false
        ) {
            throw new Exception('Caractères interdits détectés dans le nom de fichier');
        }
        
        // Vérifier qu'il n'y a pas de slash (sauf si c'est un chemin légitime dans le dossier autorisé)
        // On accepte les slashs mais on vérifiera plus tard avec realpath()
        $pathParts = explode('/', str_replace('\\', '/', $filename));
        foreach ($pathParts as $part) {
            if ($part === '..' || $part === '.') {
                throw new Exception('Caractères interdits détectés dans le nom de fichier');
            }
        }
        
        // Normaliser les retours à la ligne et tabulations en espaces
        $filename = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $filename);
        
        // Nettoyer les espaces multiples
        $filename = preg_replace('/\s+/', ' ', trim($filename));
        
        if (empty($filename)) {
            throw new Exception('Nom de fichier invalide');
        }
        
        return $filename;
    }

    /**
     * Valide l'extension du fichier
     * 
     * @param string $filename Nom du fichier
     * @return bool
     */
    public static function validateExtension(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, self::$allowedExtensions);
    }

    /**
     * Vérifie qu'un chemin est dans un dossier autorisé
     * 
     * @param string $filePath Chemin complet du fichier
     * @param string $allowedDirectory Identifiant du dossier autorisé
     * @return bool
     */
    public static function isPathInAllowedDirectory(string $filePath, string $allowedDirectory = 'uploads'): bool
    {
        self::initializeDirectories();
        
        if (!isset(self::$allowedDirectories[$allowedDirectory])) {
            return false;
        }
        
        $allowedPath = self::$allowedDirectories[$allowedDirectory];
        
        if (empty($allowedPath)) {
            return false;
        }
        
        $realFilePath = realpath($filePath);
        $realAllowedPath = realpath($allowedPath);
        
        // Si realpath retourne false, le fichier/dossier n'existe pas
        if ($realFilePath === false || $realAllowedPath === false) {
            return false;
        }
        
        // Vérifier que le fichier est bien dans le dossier autorisé
        return strpos($realFilePath, $realAllowedPath) === 0;
    }

    /**
     * Télécharge un fichier de manière sécurisée
     * 
     * @param array $options Options contenant 'file' et optionnellement 'directory'
     * @return bool|int Nombre d'octets lus, ou false en cas d'erreur
     * @throws Exception Si le fichier est invalide ou non autorisé
     */
    public static function secureDownload(array $options)
    {
        if (!isset($options["file"]) || empty($options["file"])) {
            throw new Exception('Nom de fichier manquant');
        }

        // Nettoyer le nom de fichier
        $filename = self::sanitizeFilename($options["file"]);
        
        // Valider l'extension
        if (!self::validateExtension($filename)) {
            throw new Exception('Type de fichier non autorisé');
        }
        
        // Déterminer le dossier autorisé
        $directory = $options["directory"] ?? 'uploads';
        self::initializeDirectories();
        
        if (!isset(self::$allowedDirectories[$directory])) {
            throw new Exception('Dossier non autorisé');
        }
        
        // Construire le chemin complet
        $filepath = self::$allowedDirectories[$directory] . DIRECTORY_SEPARATOR . $filename;
        
        // Si le fichier n'existe pas avec le nom nettoyé, chercher dans le dossier
        // des fichiers dont le nom nettoyé correspond (pour gérer les \n dans les noms)
        if (!file_exists($filepath)) {
            $found = false;
            $uploadDir = self::$allowedDirectories[$directory];
            
            if ($uploadDir !== null && is_dir($uploadDir)) {
                $files = scandir($uploadDir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;

                    // Défense en profondeur : forcer basename() pour éviter
                    // tout chemin relatif/absolu venant du système de fichiers
                    $safeFile = basename($file);
                    if ($safeFile !== $file || strpos($safeFile, '..') !== false) {
                        continue;
                    }

                    // Nettoyer le nom du fichier sur le disque de la même manière
                    $cleanedFileOnDisk = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $safeFile);
                    $cleanedFileOnDisk = preg_replace('/\s+/', ' ', trim($cleanedFileOnDisk));

                    // Comparer avec le nom demandé nettoyé
                    if ($cleanedFileOnDisk === $filename) {
                        $filepath = $uploadDir . DIRECTORY_SEPARATOR . $safeFile;
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found) {
                throw new Exception('Fichier introuvable: ' . $filename);
            }
        }
        
        // Vérifier que c'est bien un fichier (pas un dossier ou lien symbolique)
        if (!is_file($filepath) || is_link($filepath)) {
            throw new Exception('Type de ressource non autorisé');
        }
        
        // Vérifier que le fichier est bien dans le dossier autorisé (protection Path Traversal)
        if (!self::isPathInAllowedDirectory($filepath, $directory)) {
            throw new Exception('Accès non autorisé au fichier');
        }
        
        // Envoyer les headers de téléchargement
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Nettoyer le buffer de sortie
        if (ob_get_level()) {
            ob_clean();
        }
        flush();
        
        // Lire et envoyer le fichier
        return readfile($filepath);
    }

    /**
     * Logger une tentative d'accès suspect
     * 
     * @param string $filename Nom du fichier demandé
     * @param string $userNni NNI de l'utilisateur
     * @param string $reason Raison du blocage
     */
    public static function logSuspiciousAccess(string $filename, string $userNni = 'unknown', string $reason = ''): void
    {
        $sanitize = static fn(string $v): string => str_replace(["\r\n", "\r", "\n"], ' ', $v);

        $logMessage = sprintf(
            "[%s] SECURITY WARNING - Path Traversal attempt: User=%s, File=%s, Reason=%s\n",
            date('Y-m-d H:i:s'),
            $sanitize($userNni),
            $sanitize($filename),
            $sanitize($reason)
        );
        
        // Logger dans un fichier de sécurité
        $logFile = getenv("DEFAULT_LOGS_PATH") . DIRECTORY_SEPARATOR . 'security-' . date('Y-m-d') . '.log';
        error_log($logMessage, 3, $logFile);
        
        // Optionnel: envoyer une alerte email pour les tentatives suspectes
    }

    /**
     * Ajouter une extension autorisée
     * 
     * @param string|array $extensions Extension(s) à ajouter
     */
    public static function addAllowedExtension($extensions): void
    {
        if (is_string($extensions)) {
            $extensions = [$extensions];
        }
        
        self::$allowedExtensions = array_unique(array_merge(
            self::$allowedExtensions,
            array_map('strtolower', $extensions)
        ));
    }

    /**
     * Ajouter un dossier autorisé
     * 
     * @param string $key Identifiant du dossier
     * @param string $path Chemin absolu du dossier
     */
    public static function addAllowedDirectory(string $key, string $path): void
    {
        self::initializeDirectories();
        self::$allowedDirectories[$key] = realpath($path) ?: $path;
    }
}
