<?php

namespace App\Dto;

use App\Entity\BootTemplate;
use App\Entity\Host;
use App\Validator\UniqueEntity;
use Exception;
use IPLib\Address\IPv4;
use IPLib\Range\Subnet as SubnetRange;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use xTom\SOAP\MacAddress;
use xTom\SOAP\Validator\MacAddress as MacAddressConstraint;
use function array_values;
use function mb_strlen;
use function random_int;

#[UniqueEntity(fields: ['macAddress'], groups: ['Query'])]
#[UniqueEntity(fields: ['ipAddress'], groups: ['Query'])]
#[Assert\GroupSequence(['HostDto', 'Query'])]
class HostDto
{
    use InstantiatorTrait;

    public string $entityClass = Host::class;

    public const EXPIRES_AFTER = [
        'expires.1r' => 1,
        'expires.1h' => 3600,
        'expires.1d' => 86400,
        'expires.1w' => 604800,
        'expires.30d' => 2592000,
    ];

    public const NETMASKS = [
        '255.255.255.0 (/24)' => 24,
        '255.255.0.0 (/16)' => 16,
        '255.0.0.0 (/8)' => 8,
        '255.255.255.255 (/32)' => 32,
        '255.255.255.254 (/31)' => 31,
        '255.255.255.252 (/30)' => 30,
        '255.255.255.248 (/29)' => 29,
        '255.255.255.240 (/28)' => 28,
        '255.255.255.224 (/27)' => 27,
        '255.255.255.192 (/26)' => 26,
        '255.255.255.128 (/25)' => 25,
        '255.255.254.0 (/23)' => 23,
        '255.255.252.0 (/22)' => 22,
        '255.255.248.0 (/21)' => 21,
        '255.255.240.0 (/20)' => 20,
        '255.255.224.0 (/19)' => 19,
        '255.255.192.0 (/18)' => 18,
        '255.255.128.0 (/17)' => 17,
        '255.254.0.0 (/15)' => 15,
        '255.252.0.0 (/14)' => 14,
        '255.248.0.0 (/13)' => 13,
        '255.240.0.0 (/12)' => 12,
        '255.224.0.0 (/11)' => 11,
        '255.192.0.0 (/10)' => 10,
        '255.128.0.0 (/9)' => 9,
        '254.0.0.0 (/7)' => 7,
        '252.0.0.0 (/6)' => 6,
        '248.0.0.0 (/5)' => 5,
        '240.0.0.0 (/4)' => 4,
        '224.0.0.0 (/3)' => 3,
        '192.0.0.0 (/2)' => 2,
        '128.0.0.0 (/1)' => 1,
        '0.0.0.0 (/0)' => 0
    ];

    protected ?Host $entity = null;

    #[Assert\Type('string')]
    public ?string $hostname = null;

    public string $macAddress;

    public string $ipAddress;

    public int $prefix = 24;

    public string $gateway;

    public array $dns = ['185.222.222.222', '45.11.45.11'];

    public ?string $ipxeScript = null;

    public ?string $preseed = null;

    #[Assert\Type(BootTemplate::class)]
    public ?BootTemplate $bootTemplate = null;

    public ?int $expiresAfter = 86400;

    public null|string $rootPassword = null;

    public static function fromEntity(Host $entity): static
    {
        $dto = new static();
        $dto->entity = $entity;
        $dto->hostname = $entity->getHostname();
        $dto->ipAddress = (string) $entity->getIpAddress();
        $dto->macAddress = (string) $entity->getMacAddress();
        $dto->ipxeScript = $entity->getIpxeScript();
        $dto->prefix = $entity->getPrefix();
        $dto->gateway = $entity->getGateway()->toString();
        $dto->dns = $entity->getDns();
        $dto->bootTemplate = $entity->getBootTemplate();
        $dto->preseed = $entity->getPreseed();
        $dto->rootPassword = $entity->getRootPassword();

        return $dto;
    }

    public function toEntity(): Host
    {
        $entity = $this->entity ?? new Host();
        $entity->setIpAddress(IPv4::parseString($this->ipAddress));
        $entity->setMacAddress(MacAddress::fromString($this->macAddress));
        $this->updateEntity($entity);

        return $this->entity;
    }

    public function toEntityForValidation(): Host
    {
        $entity = $this->entity ?? $this->instantiate(Host::class);
        $entity->setIpAddress(IPv4::parseString($this->ipAddress));
        $entity->setMacAddress(MacAddress::fromString($this->macAddress));

        return $entity;
    }

    public function updateEntity(Host $entity): void
    {
        $entity->setHostname($this->hostname);
        $entity->setIpxeScript($this->ipxeScript);
        $entity->setPreseed($this->preseed);
        $entity->setBootTemplate($this->bootTemplate);
        $entity->setRange(SubnetRange::parseString($entity->getIpAddress()->toString() . '/' . $this->prefix));
        $entity->setGateway(IPv4::parseString($this->gateway));
        $entity->setDns($this->dns);

        if (1 === $this->expiresAfter) {
            $entity->setReachesToExpire($this->expiresAfter);
            $entity->setExpiresAt(null);
        } else if (null !== $this->expiresAfter) {
            $entity->setReachesToExpire(null);
            $entity->setExpiresAfter($this->expiresAfter);
        }
        if (null === $this->rootPassword || '' === $this->rootPassword) {
            $this->rootPassword = self::random_str(14);
        }
        $entity->setRootPassword($this->rootPassword);

        $this->entity = $entity;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('macAddress', new Assert\Sequentially([
            new Assert\NotBlank(),
            new MacAddressConstraint()
        ]));

        $metadata->addPropertyConstraint('ipAddress', new Assert\Sequentially([
            new Assert\NotBlank(),
            new Assert\Ip()
        ]));
        $metadata->addPropertyConstraint('gateway', new Assert\Sequentially([
            new Assert\NotBlank(),
            new Assert\Ip()
        ]));
        $metadata->addPropertyConstraint('dns', new Assert\Sequentially([
            new Assert\NotBlank(),
            new Assert\All([
                new Assert\Ip()
            ])
        ]));
        $metadata->addPropertyConstraint('expiresAfter', new Assert\Choice(array_values(static::EXPIRES_AFTER)));
        $metadata->addPropertyConstraint('prefix', new Assert\Sequentially([
            new Assert\NotBlank(),
            new Assert\Choice(array_values(static::NETMASKS))
        ]));
    }

    private static function random_str(
        $length,
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    )
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        if ($max < 1) {
            throw new Exception('$keyspace must be at least two characters long');
        }
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }
}