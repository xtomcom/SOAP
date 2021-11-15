<?php

namespace App\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class EasyAdminExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private Security $security
    )
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', -63]
            ],
        ];
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $user = $this->security->getUser();
        if ($user instanceof UserInterface) return;
        if (
            !$exception instanceof ForbiddenActionException &&
            !$exception instanceof InsufficientEntityPermissionException
        ) return;

        throw new AccessDeniedException(previous: $exception);
    }
}