<?php

namespace App\Messenger;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use xTom\SOAP\Contracts\HostManagerInterface;

class HostDeletionHandler implements MessageHandlerInterface
{
    public function __construct(private HostManagerInterface $hostManager)
    {
    }

    public function __invoke(HostDeletion $message)
    {
        $this->hostManager->delete($message->getHostId());
        \sleep(3);
    }
}