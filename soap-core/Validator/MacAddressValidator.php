<?php

namespace xTom\SOAP\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MacAddressValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint MacAddress */

        if (false !== filter_var($value, FILTER_VALIDATE_MAC)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
