<?php

namespace App\Booting;

use App\Entity\Host;

interface TemplateInterface
{
    public function getPreseed(Host $host) : string;
    public function getIpxeScript(Host $host, string $preseedUrl) : string;
}
