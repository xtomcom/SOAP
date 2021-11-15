<?php

namespace App\Entity;

use App\Repository\HostRepository;
use App\Timestamp;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use IPLib\Address\AddressInterface as IpAddressInterface;
use IPLib\Range\Subnet as SubnetRange;
use Symfony\Component\Uid\Ulid;
use xTom\SOAP\Contracts\MacAddressInterface;
use function array_unique;
use function array_values;

#[ORM\Table(name: 'hosts')]
#[ORM\Entity(repositoryClass: HostRepository::class)]
class Host
{
    use UlidTrait;

    public function __construct()
    {
        $this->id = new Ulid();
    }

    public function __toString(): string
    {
        return $this->ipAddress->toString();
    }

    public function getConfigName(): string
    {
        return $this->id->__toString();
    }

    #[ORM\Column(type: 'mac_address', unique: true)]
    private MacAddressInterface $macAddress;

    public function getMacAddress(): MacAddressInterface
    {
        return $this->macAddress;
    }

    public function setMacAddress(MacAddressInterface $macAddress): static
    {
        $this->macAddress = $macAddress;
        return $this;
    }

    #[ORM\Column(type: 'ip_address', unique: true)]
    private IpAddressInterface $ipAddress;

    public function getIpAddress(): IpAddressInterface
    {
        return $this->ipAddress;
    }

    public function setIpAddress(IpAddressInterface $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    #[ORM\Column(name: 'subnet_range', type: 'subnet_range')]
    private SubnetRange $range;

    #[ORM\Column(type: 'ip_address')]
    private IpAddressInterface $gateway;

    #[ORM\Column(type: 'simple_array')]
    private array $dns = [];

    public function getRange(): SubnetRange
    {
        return $this->range;
    }

    public function setRange(SubnetRange $range): static
    {
        $this->range = $range;

        return $this;
    }

    public function getGateway(): IpAddressInterface
    {
        return $this->gateway;
    }

    public function setGateway(IpAddressInterface $gateway): static
    {
        $this->gateway = $gateway;

        return $this;
    }

    public function getDns(): array
    {
        return $this->dns;
    }

    public function setDns(array $dns): static
    {
        $this->dns = array_unique(array_values($dns));

        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $ipxeScript = null;

    public function getIpxeScript(): ?string
    {
        return $this->ipxeScript;
    }

    public function setIpxeScript(?string $ipxeScript): static
    {
        $this->ipxeScript = $ipxeScript;

        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $preseed = null;

    public function getPreseed(): ?string
    {
        return $this->preseed;
    }

    public function setPreseed(?string $preseed): static
    {
        $this->preseed = $preseed;

        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $reachesToExpire = null;

    public function getReachesToExpire(): ?int
    {
        return $this->reachesToExpire;
    }

    public function setReachesToExpire(?int $reachesToExpire): static
    {
        $this->reachesToExpire = $reachesToExpire;

        return $this;
    }

    #[ORM\Column(type: 'timestamp', nullable: true)]
    private ?Timestamp $expiresAt = null;

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt?->toDateTime();
    }

    public function setExpiresAt(?DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt === null ?
            $expiresAt :
            Timestamp::fromDateTimeTz($expiresAt);

        return $this;
    }

    #[ORM\OneToOne(targetEntity: Operation::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Operation $registration = null;

    public function getRegistration(): ?Operation
    {
        return $this->registration;
    }

    public function setRegistration(?Operation $registration): static
    {
        $this->registration = $registration;

        return $this;
    }

    public function isPendingRegistration(): bool
    {
        return null !== $this->registration;
    }

    public function isPendingDeletion(): bool
    {
        return null !== $this->deletion;
    }

    #[ORM\OneToOne(targetEntity: Operation::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Operation $deletion = null;


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $hostname = null;

    #[ORM\ManyToOne(targetEntity: BootTemplate::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?BootTemplate $bootTemplate = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $rootPassword;

    public function getDeletion(): ?Operation
    {
        return $this->deletion;
    }

    public function setDeletion(?Operation $deletion): static
    {
        $this->deletion = $deletion;

        return $this;
    }

    public function setExpiresAfter(int $seconds): static
    {
        $this->expiresAt = Timestamp::now()->after($seconds);

        return $this;
    }


    public function getNetmask(): IpAddressInterface
    {
        return $this->range->getSubnetMask();
    }

    public function getPrefix(): int
    {
        return $this->range->getNetworkPrefix();
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function setHostname(?string $hostname): static
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function getBootTemplate(): ?BootTemplate
    {
        return $this->bootTemplate;
    }

    public function setBootTemplate(?BootTemplate $bootTemplate): static
    {
        $this->bootTemplate = $bootTemplate;

        return $this;
    }

    public function getRootPassword(): string
    {
        return $this->rootPassword;
    }

    public function setRootPassword(string $rootPassword): static
    {
        $this->rootPassword = $rootPassword;
        return $this;
    }

    public function ipxeScriptRendered()
    {
        return null;
    }

    public function preseedRendered()
    {
        return null;
    }

    public function linkUrl()
    {
        return null;
    }

    public function ipxeScriptUrl()
    {
        return null;
    }

    public function preseedUrl()
    {
        return null;
    }

    public function hasCustomPreseed()
    {
        return null !== $this->preseed && '' !== $this->preseed;
    }

    public function hasCustomIpxeScript()
    {
        return null !== $this->ipxeScript && '' !== $this->ipxeScript;
    }

    public function hasBootTemplate()
    {
        return null !== $this->bootTemplate;
    }
}
