<?php

namespace App\Messenger;

use App\Entity\Host;
use function substr;

class HostDeletion implements HostOperationInterface
{
    private string $hostId;

    public function __construct(Host $host)
    {
        $this->hostId = $host->getConfigName();
    }

    /**
     * @return string
     */
    public function getHostId(): string
    {
        return $this->hostId;
    }

    public function __toString(): string
    {
        return 'Delete ...' . substr($this->hostId, -6);
    }
}