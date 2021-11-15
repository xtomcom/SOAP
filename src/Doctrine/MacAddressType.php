<?php

namespace App\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Exception;
use xTom\SOAP\Contracts\MacAddressInterface;
use xTom\SOAP\MacAddress as MacAddressClass;

class MacAddressType extends Type
{
    public const NAME = 'mac_address';

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
            'length' => '6',
            'fixed' => true,
        ]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?MacAddressInterface
    {
        if ($value === null) {
            return null;
        }

        try {
            $macAddress = MacAddressClass::fromBinary($value);
        } catch (Exception $e) {
            throw ConversionException::conversionFailed($value, static::NAME, $e);
        }

        return $macAddress;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof MacAddressInterface) {
            throw ConversionException::conversionFailedInvalidType($value, 'binary', [MacAddressInterface::class, 'null']);
        }

        return $value->toBinary();
    }

    public function getBindingType()
    {
        return ParameterType::BINARY;
    }
}
