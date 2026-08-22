<?php

namespace App\Http\Controllers\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsCsv
{
    /**
     * Point-virgule et BOM UTF-8 : Excel FR ouvre le fichier directement,
     * sans passer par un import manuel (la virgule y est le separateur
     * decimal, pas le separateur de colonnes).
     */
    protected function streamCsv(string $nomFichier, array $entetes, iterable $lignes): StreamedResponse
    {
        return response()->streamDownload(function () use ($entetes, $lignes) {
            $flux = fopen('php://output', 'w');
            fwrite($flux, "\xEF\xBB\xBF");
            fputcsv($flux, $entetes, ';');

            foreach ($lignes as $ligne) {
                fputcsv($flux, $ligne, ';');
            }

            fclose($flux);
        }, $nomFichier, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
