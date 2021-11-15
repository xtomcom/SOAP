<?php

namespace App\Messenger;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use xTom\SOAP\Contracts\HostManagerInterface;

class HostRegistrationHandler implements MessageHandlerInterface
{
    public function __construct(private HostManagerInterface $hostManager)
    {
    }

    public function __invoke(HostRegistration $host)
    {
        $this->hostManager->register($host);
    }
}