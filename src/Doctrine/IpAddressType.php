<?php

namespace App\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
use IPLib\Address\Type as IpType;
use function Safe\pack as pack;
use function Safe\unpack as unpack;

class IpAddressType extends Type
{
    public const NAME = 'ip_address';

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
            'length' => '4',
            'fixed' => true,
        ]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (false === $ip = IPv4::fromBytes(unpack('C*', $value))) {
            throw ConversionException::conversionFailed($value, static::NAME);
        }

        return $ip;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof AddressInterface || IpType::T_IPv4 !== $value->getAddressType()) {
            throw ConversionException::conversionFailedInvalidType($value, 'binary', [IPv4::class, 'null']);
        }

        return pack('C*', ...$value->getBytes());
    }

    public function getBindingType()
    {
        return ParameterType::BINARY;
    }
}
