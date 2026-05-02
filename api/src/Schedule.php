<?php

namespace App;

use App\Message\VerifierOubliDepartMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(
                // Chaque soir à 23h30 : détecte les pointages EN_COURS non clôturés
                RecurringMessage::cron('30 23 * * *', new VerifierOubliDepartMessage()),
            )
        ;
    }
}
