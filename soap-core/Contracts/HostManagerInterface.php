<?php

namespace xTom\SOAP\Contracts;

use xTom\SOAP\Exception\HostManagerException;

/**
 * Create, update and delete DHCP server configurations
 */
interface HostManagerInterface
{
    /**
     * Create (if not exists) or update (if exists) DHCP host
     */
    public function register(HostInterface $host): void;

    /**
     * @throws HostManagerException
     */
    public function delete(string $configName): void;

    /**
     * @throws HostManagerException
     */
    public function reload(): void;
}
