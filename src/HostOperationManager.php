<?php

namespace App;

use App\Entity\Host;
use App\Entity\Operation;
use App\Messenger\HostDeletion;
use App\Messenger\HostRegistration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use xTom\SOAP\DhcpOption;
use function array_values;
use function implode;

class HostOperationManager
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function initDeletion(Host $host): ?Operation
    {
        if ($host->isPendingDeletion()) {
            // Already set
            return null;
        }

        $operation = new Operation();
        $operation->setHost($host);
        $operation->setMessage(new HostDeletion($host));
        $host->setDeletion($operation);

        return $operation;
    }

    public function initRegistration(Host $host): Operation
    {
        $operation = new Operation();
        $operation->setHost($host);
        $options = $this->createOptions($host);
        $operation->setMessage(new HostRegistration($host, $options));
        $host->setRegistration($operation);

        return $operation;
    }

    private function createOptions(Host $host): array
    {
        $options = [
            DhcpOption::NETMASK => $host->getNetmask(),
            DhcpOption::GATEWAY => $host->getGateway(),
            DhcpOption::DNS => implode(',', $host->getDns()),
            DhcpOption::BOOTFILE => 'http:' . $this->urlGenerator->generate(
                    'ipxe_script',
                    ['ulid' => $host->getId()->__toString()],
                    UrlGeneratorInterface::NETWORK_PATH
                )
        ];

        if (null !== $hostname = $host->getHostname()) {
            $options[DhcpOption::HOSTNAME] = $hostname;
        }

        foreach ($options as $tag => $value) {
            $options[$tag] = new DhcpOption($tag, $value);
        }

        return array_values($options);
    }
}