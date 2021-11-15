<?php

namespace App\Entity;

use App\Repository\BootTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: BootTemplateRepository::class)]
class BootTemplate
{
    use UlidTrait;

    public function __construct()
    {
        $this->id = new Ulid();
    }

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $ipxeScript;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $preseed;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIpxeScript(): ?string
    {
        return $this->ipxeScript;
    }

    public function setIpxeScript(?string $ipxeScript): self
    {
        $this->ipxeScript = $ipxeScript;

        return $this;
    }

    public function getPreseed(): ?string
    {
        return $this->preseed;
    }

    public function setPreseed(?string $preseed): self
    {
        $this->preseed = $preseed;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
