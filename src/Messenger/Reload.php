<?php

namespace App\Messenger;

class Reload implements HostOperationInterface
{

    public function __toString(): string
    {
        return 'Reload DHCP server';
    }
}