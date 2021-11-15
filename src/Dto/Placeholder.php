<?php

namespace App\Dto;

use Stringable;

class Placeholder implements Stringable
{
    public function __toString(): string
    {
        return '';
    }
}
