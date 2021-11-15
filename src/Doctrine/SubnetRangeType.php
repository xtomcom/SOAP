<?php

namespace App\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use IPLib\Address\Type as IpType;
use IPLib\Factory;
use IPLib\Range\Subnet;
use function array_slice;
use function implode;
use function Safe\pack as pack;
use function Safe\unpack as unpack;

class SubnetRangeType extends Type
{
    public const NAME = 'subnet_range';

    public function getName()
    {
        return static::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getBinaryTypeDeclarationSQL([
            'length' => '5',
            'fixed' => true,
        ]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }
        $bytes = unpack('C*', $value);
        if (false === $range = Factory::parseRangeString(
                implode('.', array_slice($bytes, 0, 4)) .
                '/' . $bytes[5]
            )) {
            throw ConversionException::conversionFailed($value, static::NAME);
        }

        return $range->asSubnet();
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Subnet || IpType::T_IPv4 !== $value->getAddressType()) {
            throw ConversionException::conversionFailedInvalidType($value, 'binary', ['IPv4 subnet range', 'null']);
        }

        $bytes = $value->getStartAddress()->getBytes();
        $bytes[] = $value->getNetworkPrefix();

        return pack('C*', ...$bytes);
    }

    public function getBindingType()
    {
        return ParameterType::BINARY;
    }
}
