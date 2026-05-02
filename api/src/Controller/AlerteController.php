<?php

namespace App\Controller;

use App\Entity\Alerte;
use App\Entity\User;
use App\Repository\AlerteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/alertes', name: 'api_alertes_')]
class AlerteController extends AbstractController
{
    public function __construct(
        private readonly AlerteRepository       $alerteRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'mes_alertes', methods: ['GET'])]
    public function mesAlertes(#[CurrentUser] User $user): JsonResponse
    {
        $alertes = $this->alerteRepository->findByUtilisateur($user);

        return $this->json([
            'data'         => array_map([$this, 'serializeAlerte'], $alertes),
            'non_lues'     => $this->alerteRepository->countNonLues($user),
            'message'      => 'OK',
        ]);
    }

    #[Route('/toutes', name: 'toutes', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function toutes(): JsonResponse
    {
        $alertes = $this->alerteRepository->findAllRecentes();

        return $this->json([
            'data'    => array_map([$this, 'serializeAlerte'], $alertes),
            'message' => 'OK',
        ]);
    }

    #[Route('/{id}/lire', name: 'lire', methods: ['PATCH'])]
    public function marquerLue(Alerte $alerte, #[CurrentUser] User $user): JsonResponse
    {
        if ($alerte->getUtilisateur()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Accès refusé.'], 403);
        }

        $alerte->marquerLue();
        $this->em->flush();

        return $this->json([
            'data'    => $this->serializeAlerte($alerte),
            'message' => 'Alerte marquée comme lue.',
        ]);
    }

    #[Route('/tout-lire', name: 'tout_lire', methods: ['PATCH'])]
    public function marquerToutLu(#[CurrentUser] User $user): JsonResponse
    {
        $alertes = $this->alerteRepository->findNonLuesByUtilisateur($user);
        foreach ($alertes as $alerte) {
            $alerte->marquerLue();
        }
        $this->em->flush();

        return $this->json([
            'message' => sprintf('%d alerte(s) marquée(s) comme lues.', count($alertes)),
        ]);
    }

    private function serializeAlerte(Alerte $a): array
    {
        return [
            'id'          => $a->getId(),
            'typeAlerte'  => $a->getTypeAlerte(),
            'message'     => $a->getMessage(),
            'dateCreation' => $a->getDateCreation()?->format('Y-m-d\TH:i:s'),
            'estLue'      => $a->isEstLue(),
            'recente'     => $a->isRecente(),
            'pointage'    => $a->getPointage() ? [
                'id'      => $a->getPointage()->getId(),
                'dateJour' => $a->getPointage()->getDateJour()?->format('Y-m-d'),
            ] : null,
            'utilisateur' => $a->getUtilisateur() ? [
                'id'     => $a->getUtilisateur()->getId(),
                'prenom' => $a->getUtilisateur()->getPrenom(),
                'nom'    => $a->getUtilisateur()->getNom(),
            ] : null,
        ];
    }
}
