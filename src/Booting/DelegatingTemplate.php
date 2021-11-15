<?php

namespace App\Booting;

use App\Entity\Host;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class DelegatingTemplate implements ServiceSubscriberInterface, TemplateInterface
{

    public function __construct(protected ContainerInterface $locator)
    {
    }

    public static function getSubscribedServices()
    {
        return [
            'twig' => TwigTemplate::class,
            'host' => HostTwigTemplate::class
        ];
    }

    protected function getTemplate(Host $host, bool $hasCustom): TemplateInterface
    {
        $type = 'host';
        if ($host->hasBootTemplate() && !$hasCustom) {
            $type = $host->getBootTemplate()->getType();
        }
        return $this->locator->get($type);
    }

    public function getPreseed(Host $host): string
    {
        return $this->getTemplate($host, $host->hasCustomPreseed())->getPreseed($host);
    }

    public function getIpxeScript(Host $host, string $preseedUrl): string
    {
        return $this->getTemplate($host, $host->hasCustomIpxeScript())->getIpxeScript($host, $preseedUrl);
    }
}