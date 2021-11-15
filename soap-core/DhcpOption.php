<?php

namespace xTom\SOAP;

use xTom\SOAP\Contracts\DhcpOptionInterface;

class DhcpOption implements DhcpOptionInterface
{
    public const NETMASK = 1;
    public const GATEWAY = 3;
    public const DNS = 6;
    public const HOSTNAME = 12;
    public const NTP = 42;
    public const TFTP = 66;
    public const BOOTFILE = 67;

    public function __construct(
        protected int $tag,
        protected string $value
    )
    {
    }

    public function getTag() : int
    {
        return $this->tag;
    }

    public function getValue() : string
    {
        return $this->value;
    }
}
