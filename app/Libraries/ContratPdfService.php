<?php

namespace App\Libraries;

use DateTimeImmutable;

class ContratPdfService
{
    public function genererPdfTextuel(string $titre, array $lignes, bool $forceMultiPage = false): string
    {
        $html = $this->construireHtmlContrat($titre, $lignes, $forceMultiPage);

        // 1) Dompdf (si installé via composer) : fonctionne partout, Windows compris
        $pdf = $this->genererPdfAvecDompdf($html);
        if ($this->pdfValide($pdf)) {
            return $pdf;
        }

        // 2) LibreOffice (Linux uniquement)
        $pdf = $this->genererPdfAvecLibreOffice($html);
        if ($this->pdfValide($pdf)) {
            return $pdf;
        }

        // 3) PDF natif, sans dépendance
        return $this->genererPdfFallback($titre, $lignes, $forceMultiPage);
    }

    private function pdfValide(?string $pdf): bool
    {
        return $pdf !== null && strlen($pdf) > 1500 && str_starts_with($pdf, '%PDF');
    }

    private function genererPdfAvecDompdf(string $html): ?string
    {
        if (! class_exists(\Dompdf\Dompdf::class)) {
            return null;
        }

        try {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Serif');

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();
        } catch (\Throwable $e) {
            log_message('error', 'ContratPdfService Dompdf : ' . $e->getMessage());

            return null;
        }
    }

    private function genererPdfAvecLibreOffice(string $html): ?string
    {
        if (PHP_OS_FAMILY === 'Windows' || ! function_exists('shell_exec') || ! function_exists('exec')) {
            return null;
        }

        if (trim((string) @shell_exec('command -v libreoffice 2>/dev/null')) === '') {
            return null;
        }

        $tmpDir = sys_get_temp_dir() . '/legaltech-pdf-' . bin2hex(random_bytes(6));
        if (! mkdir($tmpDir, 0775, true) && ! is_dir($tmpDir)) {
            return null;
        }

        try {
            $htmlPath = $tmpDir . '/contrat.html';
            file_put_contents($htmlPath, $html);

            $timeout = trim((string) @shell_exec('command -v timeout 2>/dev/null')) !== '' ? 'timeout 60 ' : '';
            $cmd = $timeout . 'libreoffice'
                . ' -env:UserInstallation=' . escapeshellarg('file://' . $tmpDir . '/profile')
                . ' --headless --convert-to pdf:writer_web_pdf_Export'
                . ' --outdir ' . escapeshellarg($tmpDir)
                . ' ' . escapeshellarg($htmlPath) . ' 2>&1';

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            $pdfPath = $tmpDir . '/contrat.pdf';
            if ($returnCode !== 0 || ! is_file($pdfPath)) {
                log_message('error', 'ContratPdfService LibreOffice (code ' . $returnCode . ') : ' . implode(' | ', $output));

                return null;
            }

            $pdf = file_get_contents($pdfPath);

            return $pdf !== false ? $pdf : null;
        } finally {
            $this->nettoyerDossierTemporaire($tmpDir);
        }
    }

    private function construireHtmlContrat(string $titre, array $lignes, bool $forceMultiPage): string
    {
        $titre = htmlspecialchars($titre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $corps = '';

        foreach ($this->organiserLignesPourHtml($lignes, $forceMultiPage) as $bloc) {
            if ($bloc['type'] === 'title') {
                $corps .= '<h1 class="doc-title">' . $bloc['texte'] . '</h1>';
                continue;
            }

            if ($bloc['type'] === 'subtitle') {
                $corps .= '<p class="doc-subtitle">' . $bloc['texte'] . '</p>';
                continue;
            }

            if ($bloc['type'] === 'section') {
                $corps .= '<h2 class="section-title">' . $bloc['texte'] . '</h2>';
                continue;
            }

            if ($bloc['type'] === 'bullet') {
                $corps .= '<div class="bullet">' . $bloc['texte'] . '</div>';
                continue;
            }

            if ($bloc['type'] === 'spacer') {
                $corps .= '<div class="spacer"></div>';
                continue;
            }

            if ($bloc['type'] === 'page-break') {
                $corps .= '<div class="page-break"></div>';
                continue;
            }

            $corps .= '<p class="paragraph">' . $bloc['texte'] . '</p>';
        }

        return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>' . $titre . '</title><style>' .
            '@page{margin:25mm 20mm 20mm 20mm;size:A4;}' .
            'body{font-family:"DejaVu Serif","Times New Roman",Times,serif;color:#000;margin:0;padding:0;background:#fff;font-size:11pt;}' .
            'h1.doc-title{font-size:13pt;font-weight:bold;text-align:left;margin:0;line-height:1.6;color:#000;text-transform:uppercase;}' .
            'p.doc-subtitle{font-size:11pt;text-align:left;color:#000;margin:0;line-height:1.6;font-weight:normal;}' .
            'h2.section-title{font-size:11pt;font-weight:bold;text-align:left;margin:0;padding:0;color:#000;page-break-after:avoid;line-height:1.6;}' .
            'p.paragraph{font-size:11pt;line-height:1.6;margin:0;text-align:justify;color:#000;}' .
            '.bullet{font-size:11pt;line-height:1.6;margin:0 0 0 20px;text-align:justify;}' .
            '.spacer{height:12px;margin:0;padding:0;}' .
            '.page-break{page-break-before:always;height:0;margin:0;padding:0;}' .
            '</style></head><body>' . $corps . '</body></html>';
    }

    private function organiserLignesPourHtml(array $lignes, bool $forceMultiPage): array
    {
        $items = [];
        $indexNonVide = 0;
        $articleCommence = false;
        $apresSautPage = false;

        foreach ($lignes as $ligne) {
            $ligne = trim((string) $ligne);
            $texte = htmlspecialchars($ligne, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Ignorer les lignes vides
            if ($ligne === '') {
                continue;
            }

            if ($ligne === '[[PAGE_BREAK]]') {
                $items[] = ['type' => 'page-break', 'texte' => ''];
                $apresSautPage = true;
                continue;
            }

            if ($apresSautPage) {
                $items[] = ['type' => 'title', 'texte' => $texte];
                $items[] = ['type' => 'spacer', 'texte' => ''];
                $apresSautPage = false;
                $indexNonVide++;
                continue;
            }

            // Titre du document (première ligne non-vide)
            if ($indexNonVide === 0) {
                $items[] = ['type' => 'title', 'texte' => $texte];
                $items[] = ['type' => 'spacer', 'texte' => ''];
                $indexNonVide++;
                continue;
            }

            $isArticle = preg_match('/^Article\s+\d+/iu', $ligne) === 1;

            if ($isArticle && ! $articleCommence) {
                $articleCommence = true;
                if (! empty($items) && end($items)['type'] !== 'spacer') {
                    $items[] = ['type' => 'spacer', 'texte' => ''];
                }
            }

            // Préambule (avant le premier article)
            if (! $articleCommence) {
                if ($indexNonVide === 1) {
                    $items[] = ['type' => 'subtitle', 'texte' => $texte];
                    $items[] = ['type' => 'spacer', 'texte' => ''];
                    $indexNonVide++;
                    continue;
                }

                $items[] = ['type' => 'paragraph', 'texte' => $texte];
                $items[] = ['type' => 'spacer', 'texte' => ''];
                $indexNonVide++;
                continue;
            }

            // Articles
            if ($isArticle) {
                if (! empty($items) && end($items)['type'] !== 'spacer') {
                    $items[] = ['type' => 'spacer', 'texte' => ''];
                }

                $items[] = ['type' => 'section', 'texte' => $texte];
                $items[] = ['type' => 'spacer', 'texte' => ''];
                continue;
            }

            // Listes
            if (preg_match('/^[•\-\*]/u', $ligne) === 1) {
                $items[] = ['type' => 'bullet', 'texte' => $texte];
                continue;
            }

            $items[] = ['type' => 'paragraph', 'texte' => $texte];
        }

        if ($forceMultiPage && count($items) < 30) {
            $items[] = ['type' => 'spacer', 'texte' => ''];
            $items[] = ['type' => 'paragraph', 'texte' => htmlspecialchars('Fin du contrat.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')];
        }

        return $items;
    }

    private function genererPdfFallback(string $titre, array $lignes, bool $forceMultiPage): string
    {
        $lignes = array_values(array_filter(
            array_map(static fn ($ligne) => trim((string) $ligne), $lignes),
            static fn ($ligne) => $ligne !== ''
        ));

        if ($lignes === []) {
            $lignes = ['Contrat de bail'];
        }

        $titreTexte = $this->latin1($titre);
        $yDebut = 750;
        $yMin = 60;

        // Mise en page : retour à la ligne automatique + pagination selon la hauteur
        $pages = [];
        $page = [];
        $y = $yDebut;

        foreach ($lignes as $ligne) {
            $isSection = preg_match('/^(Article\s+\d+|CONTRAT|FICHE FISCALE)/iu', $ligne) === 1;
            $texte = $this->latin1($ligne);
            $taille = $isSection ? 12 : 10;
            $interligne = $isSection ? 18 : 14;
            $largeurMax = $isSection ? 75 : 92;

            if ($isSection && $y < $yDebut) {
                $y -= 6;
            }

            foreach (explode("\n", wordwrap($texte, $largeurMax, "\n", true)) as $morceau) {
                if ($y < $yMin) {
                    $pages[] = $page;
                    $page = [];
                    $y = $yDebut;
                }

                $page[] = ['texte' => $morceau, 'taille' => $taille, 'y' => $y];
                $y -= $interligne;
            }
        }

        if ($page !== []) {
            $pages[] = $page;
        }

        if ($forceMultiPage && count($pages) < 2) {
            $pages[] = [['texte' => 'Suite du contrat...', 'taille' => 10, 'y' => $yDebut]];
        }

        $objectStrings = [];
        $pageObjectIds = [];
        $nextObjectId = 4;
        $totalPages = count($pages);

        foreach ($pages as $index => $pageLignes) {
            $content = "BT\n/F1 14 Tf\n50 805 Td\n(" . $this->pdfEscape($titreTexte) . ") Tj\nET\n";
            $content .= "0.5 w\n50 795 m 545 795 l S\n";

            foreach ($pageLignes as $l) {
                $content .= "BT\n/F1 {$l['taille']} Tf\n50 {$l['y']} Td\n(" . $this->pdfEscape($l['texte']) . ") Tj\nET\n";
            }

            $numero = 'Page ' . ($index + 1) . ' / ' . $totalPages;
            $content .= "BT\n/F1 8 Tf\n490 28 Td\n(" . $this->pdfEscape($numero) . ") Tj\nET\n";

            $contentObjectId = $nextObjectId++;
            $pageObjectId = $nextObjectId++;

            $objectStrings[$contentObjectId] = sprintf("%d 0 obj\n<< /Length %d >>\nstream\n%sendstream\nendobj", $contentObjectId, strlen($content), $content);
            $objectStrings[$pageObjectId] = sprintf("%d 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>\nendobj", $pageObjectId, $contentObjectId);
            $pageObjectIds[] = $pageObjectId;
        }

        $objectStrings[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
        $objectStrings[2] = "2 0 obj\n<< /Type /Pages /Kids [" . implode(' 0 R ', $pageObjectIds) . " 0 R] /Count " . count($pageObjectIds) . " >>\nendobj";
        $objectStrings[3] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        $maxObjectId = max(array_keys($objectStrings));

        foreach (range(1, $maxObjectId) as $id) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $objectStrings[$id] . "\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxObjectId + 1) . "\n";
        $pdf .= sprintf("%010d 65535 f \n", 0);

        for ($i = 1; $i <= $maxObjectId; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . ($maxObjectId + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function nettoyerDossierTemporaire(string $dossier): void
    {
        if (! is_dir($dossier)) {
            return;
        }

        $iterateur = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dossier, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterateur as $element) {
            $element->isDir() ? @rmdir($element->getPathname()) : @unlink($element->getPathname());
        }

        @rmdir($dossier);
    }

    public function latin1(string $texte): string
    {
        $texte = str_replace(['’', '‘', '“', '”', '…'], ["'", "'", '"', '"', '...'], $texte);
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