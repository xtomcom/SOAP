<?php

namespace App\Dto;

use Doctrine\Instantiator\Instantiator;

trait InstantiatorTrait
{
    protected ?Instantiator $instantiator = null;

    protected function instantiate(string $entityFqcn)
    {
        if (null === $this->instantiator) {
            $this->instantiator = new Instantiator();
        }

        return $this->instantiator->instantiate($entityFqcn);
    }
}