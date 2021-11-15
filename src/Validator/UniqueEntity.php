<?php

namespace App\Validator;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity as BaseConstraint;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class UniqueEntity extends BaseConstraint
{
    public function validatedBy()
    {
        return UniqueEntityValidator::class;
    }
}
