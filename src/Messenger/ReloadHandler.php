<?php

namespace App\Messenger;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use xTom\SOAP\Contracts\HostManagerInterface;

class ReloadHandler implements MessageHandlerInterface
{
    public function __construct(private HostManagerInterface $hostManager)
    {
    }

    public function __invoke(Reload $message)
    {
        $this->hostManager->reload();
    }
}
