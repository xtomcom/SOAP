<?php

namespace xTom\SOAP\Contracts;

use Stringable;

/**
 * A host running in the DHCP subnet to get IP
 * assigned by DHCP server and booted with PXE
 */
interface HostInterface
{
    // Host identifier
    public function getConfigName(): string|Stringable;

    // MAC address
    public function getMacAddress(): string|Stringable;

    /**
     * DHCP options
     *
     * @return DhcpOptionInterface[]
     */
    public function getOptions(): array;
}
