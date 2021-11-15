<?php

namespace App\Booting;

use App\Entity\Host;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;

abstract class AbstractTwigTemplate implements TemplateInterface
{
    public function __construct(private TwigEnvironment $twig)
    {
    }

    abstract protected function getPreseedSource(Host $host): ?string;

    abstract protected function getIpxeScriptSource(Host $host): ?string;

    private function render(?string $source, array $options = []): string
    {
        if (null === $source) {
            return '';
        }

        try {
            $result = $this->twig->createTemplate($source)->render($options);
        } catch (TwigError $e) {
            $result = $e::class . ': ' . $e->getMessage();
        }

        return $result . "\n";
    }

    public function getPreseed(Host $host): string
    {
        return $this->render($this->getPreseedSource($host), [
            'host' => $host
        ]);
    }

    public function getIpxeScript(Host $host, string $preseedUrl): string
    {
        return $this->render($this->getIpxeScriptSource($host), [
            'host' => $host,
            'preseed_url' => $preseedUrl
        ]);
    }
}