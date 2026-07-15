<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Repository\PointageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\PdfGeneratorService;

class ExportService
{
    private string $exportsDir;

    public function __construct(
        private readonly PointageRepository    $pointageRepository,
        private readonly DocumentRepository    $documentRepository,
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface   $httpClient,
        private readonly LoggerInterface       $logger,
        private readonly PdfGeneratorService   $pdfGenerator,
        string $exportsDir,
        private readonly string $gotenbergUrl,
    ) {
        $this->exportsDir = $exportsDir;
        if (!is_dir($this->exportsDir)) {
            mkdir($this->exportsDir, 0755, true);
        }
    }

    public function genererCsv(User $user, \DateTimeInterface $debut, \DateTimeInterface $fin): Document
    {
        $pointages = $this->pointageRepository->findByPeriode($user, $debut, $fin);

        $filename = sprintf(
            'export_%s_%s_%s.csv',
            $user->getId(),
            $debut->format('Ymd'),
            $fin->format('Ymd'),
        );
        $filepath = $this->exportsDir . '/' . $filename;

        $handle = fopen($filepath, 'w');
        if (false === $handle) {
            throw new \RuntimeException('Impossible de créer le fichier CSV.');
        }

        // BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'Date', 'Site', 'Heure Arrivée', 'Heure Départ', 'Durée (min)', 'Statut', 'Anomalie',
        ], ';');

        foreach ($pointages as $p) {
            fputcsv($handle, [
                $p->getDateJour()?->format('d/m/Y'),
                $p->getSite()?->getNom() ?? 'N/A',
                $p->getHeureArrivee()?->format('H:i:s'),
                $p->getHeureDepart()?->format('H:i:s') ?? '',
                $p->getDureeMinutes() ?? '',
                $p->getStatut(),
                $p->isEstAnomalie() ? 'Oui' : 'Non',
            ], ';');
        }

        fclose($handle);

        return $this->creerDocument($user, Document::TYPE_CSV, $filepath);
    }

    public function genererPdf(User $user, \DateTimeInterface $debut, \DateTimeInterface $fin): Document
    {
        $pointages = $this->pointageRepository->findByPeriode($user, $debut, $fin);
        $html      = $this->buildHtmlReport($user, $debut, $fin, $pointages);

        $filename = sprintf(
            'export_%s_%s_%s.pdf',
            $user->getId(),
            $debut->format('Ymd'),
            $fin->format('Ymd'),
        );
        $filepath = $this->exportsDir . '/' . $filename;

        // Tentative de génération via Gotenberg, fallback DOMPDF
        $pdfContent = $this->convertHtmlToPdfViaGotenberg($html);

        if (null === $pdfContent) {
            $this->logger->info('[ExportService] Gotenberg indisponible, utilisation de DOMPDF.');
            $this->pdfGenerator->generateFromHtml($html, $filepath);
        } else {
            if (false === file_put_contents($filepath, $pdfContent)) {
                throw new \RuntimeException('Impossible d\'écrire le fichier PDF.');
            }
        }

        return $this->creerDocument($user, Document::TYPE_PDF, $filepath);
    }

    private function convertHtmlToPdfViaGotenberg(string $html): ?string
    {
        try {
            $boundary = uniqid('horosphere_', true);
            $body     = "--{$boundary}\r\n"
                . "Content-Disposition: form-data; name=\"files\"; filename=\"index.html\"\r\n"
                . "Content-Type: text/html\r\n\r\n"
                . $html . "\r\n"
                . "--{$boundary}--\r\n";

            $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/chromium/convert/html', [
                'headers' => ['Content-Type' => "multipart/form-data; boundary={$boundary}"],
                'body'    => $body,
                'timeout' => 30,
            ]);

            if (200 !== $response->getStatusCode()) {
                $this->logger->error('[ExportService] Gotenberg a retourné HTTP ' . $response->getStatusCode());
                return null;
            }

            return $response->getContent();
        } catch (\Throwable $e) {
            $this->logger->error('[ExportService] Erreur Gotenberg : ' . $e->getMessage());
            return null;
        }
    }

    private function buildHtmlReport(
        User $user,
        \DateTimeInterface $debut,
        \DateTimeInterface $fin,
        array $pointages,
    ): string {
        $rows = '';
        $totalMinutes = 0;

        foreach ($pointages as $p) {
            $duree = $p->getDureeMinutes();
            $totalMinutes += $duree ?? 0;
            $statut = $p->getStatut();
            $color = match ($statut) {
                'VALIDE'    => '#1A9E5C',
                'ANOMALIE'  => '#CC3B3B',
                'HORS_ZONE' => '#B06820',
                default     => '#3B3BCC',
            };
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s min</td><td style="color:%s;font-weight:bold">%s</td></tr>',
                htmlspecialchars($p->getDateJour()?->format('d/m/Y') ?? ''),
                htmlspecialchars($p->getSite()?->getNom() ?? 'N/A'),
                htmlspecialchars($p->getHeureArrivee()?->format('H:i') ?? ''),
                htmlspecialchars($p->getHeureDepart()?->format('H:i') ?? '-'),
                $duree ?? '-',
                $color,
                htmlspecialchars($statut),
            );
        }

        $totalH = intdiv($totalMinutes, 60);
        $totalM = $totalMinutes % 60;
        $totalMStr = str_pad((string) $totalM, 2, '0', STR_PAD_LEFT);
        $nbPointages = count($pointages);
        $fullName = htmlspecialchars($user->getFullName());
        $email = htmlspecialchars($user->getEmail());
        $debutStr = $debut->format('d/m/Y');
        $finStr = $fin->format('d/m/Y');
        $generatedAt = (new \DateTime())->format('d/m/Y à H:i');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
        <meta charset="UTF-8">
        <title>Rapport Horosphere — {$fullName}</title>
        <style>
          body { font-family: Arial, sans-serif; color: #18182E; margin: 40px; }
          h1 { color: #3B3BCC; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th { background: #3B3BCC; color: white; padding: 10px; text-align: left; }
          td { padding: 8px 10px; border-bottom: 1px solid #DDDDE8; }
          tr:nth-child(even) { background: #F4F4F6; }
          .total { margin-top: 20px; font-weight: bold; font-size: 16px; }
        </style>
        </head>
        <body>
        <h1>◎ Horosphere — Rapport de présence</h1>
        <p><strong>Employé :</strong> {$fullName} ({$email})</p>
        <p><strong>Période :</strong> {$debutStr} au {$finStr}</p>
        <p><strong>Généré le :</strong> {$generatedAt}</p>
        <table>
          <thead>
            <tr><th>Date</th><th>Site</th><th>Arrivée</th><th>Départ</th><th>Durée</th><th>Statut</th></tr>
          </thead>
          <tbody>{$rows}</tbody>
        </table>
        <div class="total">Total : {$totalH}h{$totalMStr} ({$totalMinutes} min) sur {$nbPointages} jours</div>
        </body>
        </html>
        HTML;
    }

    private function creerDocument(User $user, string $type, string $filepath): Document
    {
        $document = new Document();
        $document->setUtilisateur($user);
        $document->setTypeDocument($type);
        $document->setCheminFichier($filepath);

        $this->documentRepository->save($document);

        return $document;
    }
}
