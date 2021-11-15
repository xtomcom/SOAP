<?php

namespace App\Entity;

use DateTimeImmutable;
use Symfony\Component\Uid\Ulid;
use Doctrine\ORM\Mapping as ORM;

trait UlidTrait
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->id->getDateTime();
    }
}