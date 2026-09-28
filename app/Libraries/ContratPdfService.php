<?php

namespace App\Libraries;

use DateTimeImmutable;

class ContratPdfService
{
    public function genererPdfTextuel(string $titre, array $lignes): string
    {
        $lignes = array_values(array_filter(array_map(static fn ($ligne) => trim((string) $ligne), $lignes), static fn ($ligne) => $ligne !== ''));

        if ($lignes === []) {
            $lignes = ['Contrat de bail'];
        }

        $content = "BT\n/F1 16 Tf\n50 790 Td\n(" . $this->pdfEscape($this->latin1($titre)) . ") Tj\n/F1 10 Tf\n0 -24 Td\n";

        foreach ($lignes as $ligne) {
            $content .= sprintf("(%s) Tj\n0 -15 Td\n", $this->pdfEscape($this->latin1($ligne)));
        }

        $content .= "ET\n";

        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj',
            '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            sprintf('5 0 obj << /Length %d >> stream\n%s\nendstream\nendobj', strlen($content), $content),
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= '0 ' . (count($objects) + 1) . "\n";
        $pdf .= sprintf("%010d 65535 f \n", 0);
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n";
        $pdf .= '<< /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= 'startxref' . "\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    public function latin1(string $texte): string
    {
        $converti = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texte);

        return $converti === false ? $texte : $converti;
    }

    private function pdfEscape(string $texte): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texte);
    }

    public function formatDate(?string $date): string
    {
        if (empty($date)) {
            return '—';
        }

        try {
            return (new DateTimeImmutable($date))->format('d/m/Y');
        } catch (\Throwable) {
            return $date;
        }
    }
}
