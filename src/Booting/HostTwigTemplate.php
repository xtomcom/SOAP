<?php

namespace App\Booting;

use App\Entity\Host;

class HostTwigTemplate extends AbstractTwigTemplate
{
    protected function getPreseedSource(Host $host): ?string
    {
        return $host->getPreseed();
    }

    protected function getIpxeScriptSource(Host $host): ?string
    {
        return $host->getIpxeScript();
    }
}