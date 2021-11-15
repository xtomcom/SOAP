<?php

namespace App\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

trait DoctrineTypeTrait
{
    /** @var EntityManager */
    protected $_em;

    /** @var ClassMetadata */
    protected $_class;

    protected function getDoctrineType(string $fieldName) : string
    {
        return $this->_class->fieldMappings[$fieldName]['type'];
    }
}