<?php

namespace App\Messenger;

use App\Entity\Host;
use xTom\SOAP\Contracts\HostInterface;
use function substr;

class HostRegistration implements HostInterface, HostOperationInterface
{
    protected string $configName;
    protected string $macAddress;
    protected string $ipAddress;
    protected array $options;

    public function __construct(Host $host, array $options)
    {
        $this->configName = $host->getConfigName();
        $this->macAddress = $host->getMacAddress();
        $this->ipAddress = $host->getIpAddress();
        $this->options = $options;
    }

    public function getConfigName(): string
    {
        return $this->configName;
    }

    public function getMacAddress(): string
    {
        return $this->macAddress;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function __toString(): string
    {
        return 'Apply ...' . substr($this->configName, -6) . " ($this->ipAddress)";
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}