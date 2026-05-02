<?php

namespace App\EventListener;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Protection brute force sur /api/auth/login.
 * Utilise le pool de cache PSR-6 (symfony/cache, déjà disponible).
 *
 * Limites :
 *  - 10 tentatives par IP sur 60 secondes
 *  - 5 tentatives par email sur 300 secondes
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class LoginRateLimitListener
{
    private const IP_MAX    = 10;
    private const IP_TTL    = 60;    // secondes
    private const EMAIL_MAX = 5;
    private const EMAIL_TTL = 300;   // secondes

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getPathInfo() !== '/api/auth/login' || $request->getMethod() !== 'POST') {
            return;
        }

        // ── Limite par IP ──────────────────────────────────────────────
        $ip  = $request->getClientIp() ?? 'unknown';
        $key = 'rl_ip_' . hash('sha256', $ip);

        if ($this->isRateLimited($key, self::IP_MAX, self::IP_TTL)) {
            $event->setResponse(new JsonResponse(
                ['message' => 'Trop de tentatives depuis votre adresse IP. Réessayez dans ' . self::IP_TTL . ' secondes.'],
                429,
                ['Retry-After' => self::IP_TTL],
            ));
            return;
        }

        // ── Limite par email ───────────────────────────────────────────
        $body  = json_decode((string) $request->getContent(), true) ?? [];
        $email = trim((string) ($body['email'] ?? ''));

        if ('' !== $email) {
            $emailKey = 'rl_email_' . hash('sha256', $email);
            if ($this->isRateLimited($emailKey, self::EMAIL_MAX, self::EMAIL_TTL)) {
                $event->setResponse(new JsonResponse(
                    ['message' => 'Trop de tentatives pour ce compte. Réessayez dans quelques minutes.'],
                    429,
                    ['Retry-After' => self::EMAIL_TTL],
                ));
            }
        }
    }

    /**
     * Incrémente le compteur et retourne true si la limite est dépassée.
     */
    private function isRateLimited(string $key, int $max, int $ttl): bool
    {
        $item = $this->cache->getItem($key);

        $count = $item->isHit() ? (int) $item->get() : 0;
        $count++;

        $item->set($count);
        if (!$item->isHit()) {
            $item->expiresAfter($ttl);
        }
        $this->cache->save($item);

        return $count > $max;
    }
}
