<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class LoginRateLimitListener
{
    public function __construct(
        private readonly RateLimiterFactory $loginIpLimiter,
        private readonly RateLimiterFactory $loginEmailLimiter,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getPathInfo() !== '/api/auth/login' || $request->getMethod() !== 'POST') {
            return;
        }

        // Limite par IP
        $ip      = $request->getClientIp() ?? 'unknown';
        $limiterIp = $this->loginIpLimiter->create('ip-' . $ip);
        $limitIp   = $limiterIp->consume();

        if (!$limitIp->isAccepted()) {
            $event->setResponse($this->tooManyResponse(
                'Trop de tentatives depuis votre adresse IP. Réessayez dans ' .
                $limitIp->getRetryAfter()->getTimestamp() - time() . ' secondes.',
                $limitIp->getRemainingTokens(),
            ));
            return;
        }

        // Limite par email si fourni
        $body  = json_decode((string) $request->getContent(), true) ?? [];
        $email = $body['email'] ?? null;

        if (null !== $email) {
            $limiterEmail = $this->loginEmailLimiter->create('email-' . hash('sha256', (string) $email));
            $limitEmail   = $limiterEmail->consume();

            if (!$limitEmail->isAccepted()) {
                $event->setResponse($this->tooManyResponse(
                    'Trop de tentatives pour ce compte. Réessayez dans quelques minutes.',
                    $limitEmail->getRemainingTokens(),
                ));
            }
        }
    }

    private function tooManyResponse(string $message, int $remaining): JsonResponse
    {
        return new JsonResponse(
            ['message' => $message, 'remaining_attempts' => $remaining],
            429,
            ['Retry-After' => 60],
        );
    }
}
