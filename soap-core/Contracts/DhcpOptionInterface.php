<?php

namespace xTom\SOAP\Contracts;

interface DhcpOptionInterface
{
    public function getTag() : int;

    public function getValue() : string;
}