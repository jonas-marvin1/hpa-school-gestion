<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Valide un rendu audio, y compris ceux produits par le micro du navigateur.
 *
 * Pourquoi une regle dediee plutot que « mimetypes: » :
 * le conteneur WebM est generique. Un enregistrement produit par
 * MediaRecorder est detecte selon les cas comme « video/webm » ou
 * « application/octet-stream », jamais comme « audio/webm ». Une liste
 * blanche de types MIME rejetait donc les enregistrements faits dans
 * l'application — exactement ce qu'on venait d'ajouter.
 *
 * L'approche retenue croise deux signaux :
 *   1. l'extension doit appartenir a la liste des formats audio acceptes ;
 *   2. le contenu reel ne doit pas etre un type dangereux (script, page web,
 *      executable), quel que soit son nom.
 *
 * On ne se contente donc pas de l'extension — qui se falsifie — mais on
 * n'exige pas non plus un type MIME que les navigateurs ne produisent pas.
 */
class FichierAudio implements ValidationRule
{
    /** Extensions acceptees pour un rendu oral. */
    private const EXTENSIONS = ['mp3', 'm4a', 'mp4', 'wav', 'ogg', 'oga', 'webm', 'aac', '3gp', '3gpp', 'opus', 'weba'];

    /**
     * Types de contenu refuses d'office. Tout ce qui pourrait etre execute ou
     * interprete, meme renomme en .mp3.
     */
    private const MIMES_INTERDITS = [
        'text/x-php', 'application/x-php', 'application/x-httpd-php',
        'text/html', 'application/xhtml+xml', 'text/x-shellscript',
        'application/x-executable', 'application/x-dosexec',
        'application/x-msdownload', 'application/javascript', 'text/javascript',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail("Le fichier n'a pas pu être reçu. Réessayez.");
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $fail('Format audio non reconnu. Formats acceptés : MP3, M4A, WAV, OGG, WebM, AAC.');
            return;
        }

        $mime = (string) $value->getMimeType();

        if (in_array($mime, self::MIMES_INTERDITS, true)) {
            $fail("Ce fichier n'est pas un enregistrement audio.");
            return;
        }

        // Un fichier vide signale un enregistrement interrompu : mieux vaut le
        // dire ici que laisser l'apprenant croire son devoir rendu.
        if ($value->getSize() < 1024) {
            $fail("L'enregistrement semble vide. Recommencez, puis réécoutez-le avant d'envoyer.");
        }
    }
}
