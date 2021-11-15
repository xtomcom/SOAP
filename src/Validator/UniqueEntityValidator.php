<?php

namespace App\Validator;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator as BaseValidator;
use Symfony\Component\Validator\Constraint;

class UniqueEntityValidator extends BaseValidator
{
    public function validate($entity, Constraint $constraint)
    {
        /** @var UniqueEntity $constraint */
        $constraint->entityClass = $entity->entityClass ?? null;
        parent::validate($entity->toEntityForValidation(), $constraint);
    }
}