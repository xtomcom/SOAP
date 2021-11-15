<?php

namespace App\Doctrine;

use App\Timestamp;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;

class TimestampType extends DateTimeImmutableType
{
    public function getName()
    {
        return 'timestamp';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return parent::convertToDatabaseValue($value?->toDateTime(), $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value = parent::convertToPHPValue($value, $platform)) {
            return $value;
        }

        return Timestamp::fromDateTime($value);
    }
}