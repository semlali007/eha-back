<?php
namespace App\Exceptions;
class SheetException extends \Exception
{
    public static function forSheetSave($message = '')
    {
        return new self("Erreur d'ajout d'une fiche. " . $message);
    }

    public static function forSheetList($message = ''): self
    {
        return new self("Erreur de récupération de fiches. " . $message);
    }

    public static function forSheetRemove($message = ''): self
    {
        return new self('Erreur de suppression de la fiche. ' . $message);
    }

    public static function forSheetDetails($message = ''): self
    {
        return new self('Erreur de récupération de la fiche. ' . $message);
    }

    public static function forSheetUpload($message = ''): self
    {
        return new self('Erreur de stockage de document. ' . $message);
    }
}
