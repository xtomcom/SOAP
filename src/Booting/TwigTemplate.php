<?php

namespace App\Booting;

use App\Entity\Host;

class TwigTemplate extends AbstractTwigTemplate
{
    protected function getPreseedSource(Host $host): ?string
    {
        return $host->getBootTemplate()?->getPreseed();
    }

    protected function getIpxeScriptSource(Host $host): ?string
    {
        return $host->getBootTemplate()?->getIpxeScript();
    }
}