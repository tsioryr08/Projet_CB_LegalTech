<?php

namespace App\Libraries;

use DateTimeImmutable;

class ContratPdfService
{
    public function genererPdfTextuel(string $titre, array $lignes): string
    {
        $lignes = array_values(array_filter(array_map(static fn ($ligne) => trim((string) $ligne), $lignes), static fn ($ligne) => $ligne !== ''));
        $pages = array_chunk($lignes, 38);

        $objects = [];
        $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
        $objects[] = '2 0 obj << /Type /Pages /Kids [';

        $pageIds = [];
        $currentPageId = 4;
        foreach ($pages as $index => $pageLines) {
            $contentObjectId = $currentPageId + 1;
            $pageIds[] = $currentPageId;
            $objects[] = sprintf('%d 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >> endobj', $currentPageId, $contentObjectId);
            $objects[] = $this->buildContentObject($titre, $pageLines, $index + 1, count($pages), $contentObjectId);
            $currentPageId += 2;
        }

        $objects[1] .= implode(' ', array_map(static fn ($pageId) => $pageId . ' 0 R', $pageIds)) . ' ] /Count ' . count($pageIds) . ' >> endobj';
        $objects[] = '3 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= 'xref' . "\n";
        $pdf .= '0 ' . (count($objects) + 1) . "\n";
        $pdf .= sprintf("%010d 65535 f \n", 0);
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= 'startxref' . "\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function buildContentObject(string $titre, array $lignes, int $numeroPage, int $nombrePages, int $objectId): string
    {
        $stream = [];
        $stream[] = 'BT';
        $stream[] = '/F1 16 Tf';
        $stream[] = '50 790 Td';
        $stream[] = '(' . $this->pdfEscape($this->latin1($titre)) . ') Tj';
        $stream[] = '/F1 10 Tf';
        $stream[] = '0 -22 Td';
        $stream[] = '(' . $this->pdfEscape($this->latin1('Page ' . $numeroPage . '/' . $nombrePages)) . ') Tj';
        $stream[] = '0 -24 Td';

        foreach ($lignes as $ligne) {
            $stream[] = '(' . $this->pdfEscape($this->latin1($ligne)) . ') Tj';
            $stream[] = '0 -15 Td';
        }

        $stream[] = 'ET';
        $content = implode("\n", $stream);

        return sprintf('%d 0 obj << /Length %d >> stream\n%s\nendstream endobj', $objectId, strlen($content), $content);
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
