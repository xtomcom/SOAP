<?php

namespace App\Dto;


use App\Entity\BootTemplate;
use App\Entity\Subnet;
use App\Validator\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use function array_values;

#[UniqueEntity(fields: ['name'], groups: ['Query'])]
#[Assert\GroupSequence(['BootTemplateDto', 'Query'])]
class BootTemplateDto
{
    public string $entityClass = BootTemplate::class;
    public const TYPES = [
        'Twig' => 'twig'
    ];
    use InstantiatorTrait;
    private ?BootTemplate $entity = null;
    #[Assert\NotBlank]
    public string $name;
    public string $type = 'twig';
    public ?string $ipxeScript;
    public ?string $preseed;



    public static function fromEntity(BootTemplate $entity) : static
    {
        $dto = new static();
        $dto->entity = $entity;
        $dto->name = $entity->getName();
        $dto->type = $entity->getType();
        $dto->ipxeScript = $entity->getIpxeScript();
        $dto->preseed = $entity->getPreseed();

        return $dto;
    }

    public function toEntity() : BootTemplate
    {
        $this->updateEntity($this->entity ?? new BootTemplate());

        return $this->entity;
    }

    public function toEntityForValidation() : BootTemplate
    {
        $entity = $this->entity ?? $this->instantiate(BootTemplate::class);
        $entity->setName($this->name);

        return $entity;
    }

    public function updateEntity(BootTemplate $entity) : void
    {
        $entity->setName($this->name);
        $entity->settype($this->type);
        $entity->setPreseed($this->preseed);
        $entity->setIpxeScript($this->ipxeScript);
        $this->entity = $entity;
    }
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('type', new Assert\Choice(array_values(self::TYPES)));
    }
}