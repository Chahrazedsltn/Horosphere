<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\Demande;
use App\Entity\Document;
use App\Entity\User;
use App\Repository\DemandeRepository;
use App\Repository\DocumentRepository;
use App\Service\AuditService;
use App\Service\DemandeService;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/demandes', name: 'api_demandes_')]
class DemandeController extends AbstractController
{
    public function __construct(
        private readonly DemandeService         $demandeService,
        private readonly DemandeRepository      $demandeRepository,
        private readonly DocumentRepository     $documentRepository,
        private readonly AuditService           $auditService,
        private readonly PdfGeneratorService    $pdfGenerator,
        private readonly EntityManagerInterface $em,
        #[Autowire('%app.exports_dir%')] private readonly string $exportsDir,
    ) {}

    #[Route('', name: 'liste', methods: ['GET'])]
    public function liste(#[CurrentUser] User $user): JsonResponse
    {
        if (in_array('ROLE_RH', $user->getRoles(), true)) {
            $demandes = $this->demandeRepository->findAllWithUsers();
        } else {
            $demandes = $this->demandeRepository->findByUtilisateur($user);
        }

        return $this->json([
            'data'    => array_map([$this, 'serializeDemande'], $demandes),
            'message' => 'OK',
        ]);
    }

    #[Route('/en-attente', name: 'en_attente', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function enAttente(): JsonResponse
    {
        $demandes = $this->demandeRepository->findEnAttente();

        return $this->json([
            'data'    => array_map([$this, 'serializeDemande'], $demandes),
            'message' => 'OK',
        ]);
    }

    #[Route('', name: 'creer', methods: ['POST'])]
    public function creer(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['type_demande'], $data['date_debut'], $data['date_fin'])) {
            return $this->json(['message' => 'Champs obligatoires manquants.'], 422);
        }

        try {
            $demande = $this->demandeService->soumettre($user, $data);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json([
            'data'    => $this->serializeDemande($demande),
            'message' => 'Demande soumise avec succès.',
        ], 201);
    }

    #[Route('/{id}/traiter', name: 'traiter', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_RH')]
    public function traiter(Demande $demande, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $decision = $data['decision'] ?? null;

        if (!in_array($decision, [Demande::STATUT_APPROUVEE, Demande::STATUT_REJETEE], true)) {
            return $this->json(['message' => 'Décision invalide. Valeurs acceptées : APPROUVEE, REJETEE.'], 422);
        }

        /** @var User $acteur */
        $acteur = $this->getUser();

        try {
            if (Demande::STATUT_APPROUVEE === $decision) {
                $this->demandeService->approuver($demande);
                $action = AuditLog::ACTION_DEMANDE_APPROUVEE;
            } else {
                $this->demandeService->rejeter($demande);
                $action = AuditLog::ACTION_DEMANDE_REJETEE;
            }
        } catch (\LogicException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        $this->auditService->log(
            $action,
            $acteur instanceof User ? $acteur : null,
            'Demande',
            $demande->getId(),
            [
                'employe_id'   => $demande->getUtilisateur()?->getId(),
                'employe_email'=> $demande->getUtilisateur()?->getEmail(),
                'type'         => $demande->getTypeDemande(),
                'date_debut'   => $demande->getDateDebut()?->format('Y-m-d'),
                'date_fin'     => $demande->getDateFin()?->format('Y-m-d'),
            ],
        );

        return $this->json([
            'data'    => $this->serializeDemande($demande),
            'message' => 'Demande traitée.',
        ]);
    }

    #[Route('/{id}/document', name: 'generer_document', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function genererDocument(Demande $demande, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $typeDoc = $data['type_document'] ?? 'accord_conge';

        $employe = $demande->getUtilisateur();
        if (null === $employe) {
            return $this->json(['message' => 'Employé introuvable.'], 404);
        }

        /** @var User $acteur */
        $acteur = $this->getUser();

        $html = match ($typeDoc) {
            'accord_conge'  => $this->buildAccordConge($demande, $employe, $acteur),
            'refus_conge'   => $this->buildRefusConge($demande, $employe, $acteur),
            'attestation'   => $this->buildAttestation($demande, $employe, $acteur),
            default         => $this->buildAccordConge($demande, $employe, $acteur),
        };

        $filename = sprintf('doc_%s_%s_%d.pdf', $typeDoc, date('Ymd_His'), $demande->getId());
        $filepath = $this->exportsDir . '/' . $filename;

        $this->pdfGenerator->generateFromHtml($html, $filepath);

        $document = new Document();
        $document->setUtilisateur($employe);
        $document->setTypeDocument(Document::TYPE_PDF);
        $document->setCheminFichier($filepath);
        $this->em->persist($document);
        $this->em->flush();

        return $this->json([
            'data' => [
                'id'          => $document->getId(),
                'fileName'    => $document->getFileName(),
                'downloadUrl' => '/api/documents/' . $document->getId() . '/download',
            ],
            'message' => 'Document généré avec succès.',
        ], 201);
    }

    private function genererReference(Demande $demande, string $prefixe): string
    {
        return sprintf('%s-%s-%05d', $prefixe, date('Y'), $demande->getId());
    }

    private function docStyle(): string
    {
        return <<<CSS
            body { font-family: Arial, sans-serif; color: #1a1a2e; margin: 50px; line-height: 1.6; font-size: 13px; }
            .ref { text-align: right; color: #64748b; font-size: 11px; font-family: monospace; margin-bottom: 30px; }
            h2 { color: #1e293b; font-size: 17px; text-align: center; margin: 25px 0; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
            .info { margin: 20px 0; }
            .info table { width: 100%; border-collapse: collapse; }
            .info td { padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 13px; }
            .info td:first-child { background: #f8fafc; font-weight: 600; width: 180px; color: #475569; }
            .decision-ok { background: #f0fdf4; border: 2px solid #16a34a; border-radius: 6px; padding: 16px; margin: 25px 0; text-align: center; }
            .decision-ok h3 { color: #16a34a; margin: 0 0 4px; font-size: 15px; }
            .decision-ko { background: #fef2f2; border: 2px solid #dc2626; border-radius: 6px; padding: 16px; margin: 25px 0; text-align: center; }
            .decision-ko h3 { color: #dc2626; margin: 0 0 4px; font-size: 15px; }
            .sig-table { width: 100%; border: none; margin-top: 40px; }
            .sig-table td { border: none; text-align: center; width: 50%; vertical-align: top; }
            .sig-line { border-top: 1px solid #94a3b8; margin-top: 50px; padding-top: 8px; font-size: 12px; color: #64748b; }
            .footer { position: fixed; bottom: 30px; left: 50px; right: 50px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 10px; color: #94a3b8; text-align: center; }
        CSS;
    }

    private function buildAccordConge(Demande $demande, User $employe, ?User $acteur): string
    {
        $ref          = $this->genererReference($demande, 'ACC');
        $nomEmploye   = htmlspecialchars($employe->getFullName());
        $emailEmploye = htmlspecialchars($employe->getEmail());
        $departement  = htmlspecialchars($employe->getDepartement() ?? '—');
        $type         = $this->labelTypeDemande($demande->getTypeDemande());
        $dateDebut    = $demande->getDateDebut()?->format('d/m/Y') ?? '';
        $dateFin      = $demande->getDateFin()?->format('d/m/Y') ?? '';
        $duree        = $demande->getDureeJours();
        $motif        = htmlspecialchars($demande->getMotif() ?? '—');
        $dateDoc      = (new \DateTime())->format('d/m/Y');
        $nomRh        = $acteur ? htmlspecialchars($acteur->getFullName()) : 'La Direction RH';
        $style        = $this->docStyle();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><title>Accord — {$ref}</title>
        <style>{$style}</style>
        </head>
        <body>
            <div class="ref">Ref : {$ref}<br>Date : {$dateDoc}</div>
            <h2>Accord de {$type}</h2>
            <p>La presente confirme que la demande ci-dessous a ete <strong>approuvee</strong>.</p>
            <div class="info">
                <table>
                    <tr><td>Reference</td><td style="font-family:monospace;font-weight:bold">{$ref}</td></tr>
                    <tr><td>Employe</td><td>{$nomEmploye}</td></tr>
                    <tr><td>Email</td><td>{$emailEmploye}</td></tr>
                    <tr><td>Departement</td><td>{$departement}</td></tr>
                    <tr><td>Type de demande</td><td>{$type}</td></tr>
                    <tr><td>Date de debut</td><td>{$dateDebut}</td></tr>
                    <tr><td>Date de fin</td><td>{$dateFin}</td></tr>
                    <tr><td>Duree</td><td>{$duree} jour(s)</td></tr>
                    <tr><td>Motif</td><td>{$motif}</td></tr>
                </table>
            </div>
            <div class="decision-ok">
                <h3>DEMANDE APPROUVEE</h3>
                <p style="margin:0;font-size:12px;color:#64748b">Decision prise le {$dateDoc}</p>
            </div>
            <table class="sig-table"><tr>
                <td><div class="sig-line">L'employe<br><strong>{$nomEmploye}</strong></div></td>
                <td><div class="sig-line">Le responsable RH<br><strong>{$nomRh}</strong></div></td>
            </tr></table>
            <div class="footer">Document genere par Horosphere le {$dateDoc} — Ref : {$ref}</div>
        </body>
        </html>
        HTML;
    }

    private function buildRefusConge(Demande $demande, User $employe, ?User $acteur): string
    {
        $ref          = $this->genererReference($demande, 'REF');
        $nomEmploye   = htmlspecialchars($employe->getFullName());
        $emailEmploye = htmlspecialchars($employe->getEmail());
        $departement  = htmlspecialchars($employe->getDepartement() ?? '—');
        $type         = $this->labelTypeDemande($demande->getTypeDemande());
        $dateDebut    = $demande->getDateDebut()?->format('d/m/Y') ?? '';
        $dateFin      = $demande->getDateFin()?->format('d/m/Y') ?? '';
        $duree        = $demande->getDureeJours();
        $motif        = htmlspecialchars($demande->getMotif() ?? '—');
        $dateDoc      = (new \DateTime())->format('d/m/Y');
        $nomRh        = $acteur ? htmlspecialchars($acteur->getFullName()) : 'La Direction RH';
        $style        = $this->docStyle();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><title>Refus — {$ref}</title>
        <style>{$style}</style>
        </head>
        <body>
            <div class="ref">Ref : {$ref}<br>Date : {$dateDoc}</div>
            <h2>Notification de refus — {$type}</h2>
            <p>Nous avons le regret de vous informer que la demande ci-dessous a ete <strong>refusee</strong>.</p>
            <div class="info">
                <table>
                    <tr><td>Reference</td><td style="font-family:monospace;font-weight:bold">{$ref}</td></tr>
                    <tr><td>Employe</td><td>{$nomEmploye}</td></tr>
                    <tr><td>Email</td><td>{$emailEmploye}</td></tr>
                    <tr><td>Departement</td><td>{$departement}</td></tr>
                    <tr><td>Type de demande</td><td>{$type}</td></tr>
                    <tr><td>Date de debut</td><td>{$dateDebut}</td></tr>
                    <tr><td>Date de fin</td><td>{$dateFin}</td></tr>
                    <tr><td>Duree</td><td>{$duree} jour(s)</td></tr>
                    <tr><td>Motif de la demande</td><td>{$motif}</td></tr>
                </table>
            </div>
            <div class="decision-ko">
                <h3>DEMANDE REFUSEE</h3>
                <p style="margin:0;font-size:12px;color:#64748b">Decision prise le {$dateDoc}</p>
            </div>
            <table class="sig-table"><tr>
                <td><div class="sig-line">Le responsable RH<br><strong>{$nomRh}</strong></div></td>
            </tr></table>
            <div class="footer">Document genere par Horosphere le {$dateDoc} — Ref : {$ref}</div>
        </body>
        </html>
        HTML;
    }

    private function buildAttestation(Demande $demande, User $employe, ?User $acteur): string
    {
        $ref          = $this->genererReference($demande, 'ATT');
        $nomEmploye   = htmlspecialchars($employe->getFullName());
        $emailEmploye = htmlspecialchars($employe->getEmail());
        $departement  = htmlspecialchars($employe->getDepartement() ?? '—');
        $type         = $this->labelTypeDemande($demande->getTypeDemande());
        $dateDebut    = $demande->getDateDebut()?->format('d/m/Y') ?? '';
        $dateFin      = $demande->getDateFin()?->format('d/m/Y') ?? '';
        $duree        = $demande->getDureeJours();
        $dateDoc      = (new \DateTime())->format('d/m/Y');
        $nomRh        = $acteur ? htmlspecialchars($acteur->getFullName()) : 'La Direction RH';
        $style        = $this->docStyle();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><title>Attestation — {$ref}</title>
        <style>{$style}</style>
        </head>
        <body>
            <div class="ref">Ref : {$ref}<br>Date : {$dateDoc}</div>
            <h2>Attestation</h2>
            <div style="font-size:14px;margin:30px 0;line-height:1.8">
                <p>Je soussigne(e), <strong>{$nomRh}</strong>, en qualite de responsable des ressources humaines, atteste par la presente que :</p>
                <p><strong>{$nomEmploye}</strong>, employe(e) au departement <strong>{$departement}</strong> (email : {$emailEmploye}),
                beneficie d'un(e) <strong>{$type}</strong> du <strong>{$dateDebut}</strong> au <strong>{$dateFin}</strong>,
                soit une duree de <strong>{$duree} jour(s)</strong>.</p>
                <p>Cette attestation est delivree pour servir et valoir ce que de droit.</p>
                <p>Fait le {$dateDoc}.</p>
            </div>
            <table class="sig-table"><tr>
                <td><div class="sig-line">Le responsable RH<br><strong>{$nomRh}</strong></div></td>
            </tr></table>
            <div class="footer">Document genere par Horosphere le {$dateDoc} — Ref : {$ref}</div>
        </body>
        </html>
        HTML;
    }

    private function labelTypeDemande(string $type): string
    {
        return match ($type) {
            'CONGE'      => 'Congé',
            'CORRECTION' => 'Correction',
            'ABSENCE'    => 'Absence',
            'AUTRE'      => 'Autre demande',
            default      => $type,
        };
    }

    private function serializeDemande(Demande $d): array
    {
        return [
            'id'           => $d->getId(),
            'typeDemande'  => $d->getTypeDemande(),
            'statut'       => $d->getStatut(),
            'dateDebut'    => $d->getDateDebut()?->format('Y-m-d'),
            'dateFin'      => $d->getDateFin()?->format('Y-m-d'),
            'dureeJours'   => $d->getDureeJours(),
            'motif'        => $d->getMotif(),
            'dateCreation' => $d->getDateCreation()?->format('Y-m-d\TH:i:s'),
            'utilisateur'  => $d->getUtilisateur() ? [
                'id'     => $d->getUtilisateur()->getId(),
                'prenom' => $d->getUtilisateur()->getPrenom(),
                'nom'    => $d->getUtilisateur()->getNom(),
                'email'  => $d->getUtilisateur()->getEmail(),
            ] : null,
        ];
    }
}
