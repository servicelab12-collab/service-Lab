<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ensures Symfony sees HTTPS when Railway terminates TLS at the edge.
 */
final class HttpsBehindProxySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 512]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $forwardedProto = strtolower((string) $request->headers->get('X-Forwarded-Proto', ''));

        if ($forwardedProto === 'https' || str_starts_with($forwardedProto, 'https,')) {
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', '443');
        }
    }
}
