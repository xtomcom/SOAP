<?php

namespace App\Validator;

use IPLib\Address\Type;
use IPLib\Range\Subnet;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SubnetRangeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint SubnetRange */

        if ((null !== $subnet = Subnet::parseString($value)) && Type::T_IPv4 === $subnet->getAddressType()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
