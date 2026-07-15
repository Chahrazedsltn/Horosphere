<?php

namespace App\Service;

use App\Entity\Demande;
use App\Entity\User;
use App\Repository\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DemandeService
{
    public function __construct(
        private readonly DemandeRepository $demandeRepository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {}

    public function soumettre(User $user, array $data): Demande
    {
        $dateDebut = new \DateTime($data['date_debut']);
        $dateFin   = new \DateTime($data['date_fin']);

        if ($dateDebut > $dateFin) {
            throw new \InvalidArgumentException('La date de début doit être antérieure ou égale à la date de fin.');
        }

        $demande = new Demande();
        $demande->setUtilisateur($user);
        $demande->setTypeDemande($data['type_demande']);
        $demande->setDateDebut($dateDebut);
        $demande->setDateFin($dateFin);
        $demande->setMotif($data['motif'] ?? null);

        $errors = $this->validator->validate($demande);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->demandeRepository->save($demande);

        return $demande;
    }

    public function approuver(Demande $demande): void
    {
        if (!$demande->isEnAttente()) {
            throw new \LogicException('Cette demande a déjà été traitée.');
        }
        $demande->setStatut(Demande::STATUT_APPROUVEE);
        $this->em->flush();
    }

    public function rejeter(Demande $demande): void
    {
        if (!$demande->isEnAttente()) {
            throw new \LogicException('Cette demande a déjà été traitée.');
        }
        $demande->setStatut(Demande::STATUT_REJETEE);
        $this->em->flush();
    }
}
