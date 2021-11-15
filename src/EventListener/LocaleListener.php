<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListener implements EventSubscriberInterface
{
    public function __construct(private array $locales)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            // must be registered before the Locale listener
            KernelEvents::REQUEST => [
                ['onKernelRequest', 17]
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $locale = $request->getPreferredLanguage($this->locales);
        $request->setLocale($locale);
    }
}