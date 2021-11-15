<?php

namespace xTom\SOAP\Contracts;

use Stringable;

interface MacAddressInterface extends Stringable
{
    public function toBinary() : string;
    public static function fromBinary(string $value) : static;
    public static function fromString(string $value) : static;
}
