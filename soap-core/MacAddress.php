<?php

namespace xTom\SOAP;

use InvalidArgumentException;
use xTom\SOAP\Contracts\MacAddressInterface;
use function bin2hex;
use function filter_var;
use function implode;
use function Safe\hex2bin;
use function Safe\pack;
use function Safe\unpack;
use function str_replace;
use function str_split;
use function strtoupper;

class MacAddress implements MacAddressInterface
{
    public const DELIMITER = ':';
    public const ACCEPTED_DELIMITER = [':', '-'];
    public const UPPER_CASE = false;

    private string $string;

    /** @param int[] $bytes */
    private function __construct(
        private array $bytes
    )
    {
        $this->string = $this->toString();
    }

    private function toString() : string
    {
        $value = implode(static::DELIMITER, str_split(bin2hex($this->toBinary()), 2));

        return static::UPPER_CASE ? strtoupper($value) : $value;
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function toBinary(): string
    {
        return pack('C*', ...$this->bytes);
    }

    public static function fromBinary(string $value): static
    {
        $value = unpack('C*', $value);
        if (count($value) !== 6) {
            throw new InvalidArgumentException('Invalid binary MAC address');
        }
        return new static($value);
    }

    public static function fromString(string $value): static
    {
        if (false === filter_var($value, FILTER_VALIDATE_MAC)) {
            throw new InvalidArgumentException('Invalid MAC address');
        }
        $value = hex2bin(str_replace(static::ACCEPTED_DELIMITER, '', $value));

        return static::fromBinary($value);
    }
}