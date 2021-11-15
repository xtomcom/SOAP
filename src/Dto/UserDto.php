<?php

namespace App\Dto;

use App\Entity\User;
use App\Validator\UniqueEntity;
use Doctrine\Instantiator\Instantiator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['username'], groups: ['Query'])]
#[Assert\GroupSequence(['UserDto', 'Query'])]
class UserDto
{
    public string $entityClass = User::class;
    use InstantiatorTrait;

    public function __construct(protected UserPasswordHasherInterface $hasher)
    {
    }

    protected ?User $entity = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public $username;

    #[Assert\NotBlank]
    #[Assert\Type(['string', Placeholder::class])]
    protected $password;

    #[Assert\Type('array')]
    public $roles;

    public function setPassword($value): void
    {
        if (null === $value && $this->password instanceof Placeholder) {
            return;
        }

        $this->password = $value;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public static function fromEntity(User $entity, UserPasswordHasherInterface $hasher): static
    {
        $dto = new static($hasher);
        $dto->entity = $entity;
        $dto->username = $entity->getUserIdentifier();
        $dto->password = new Placeholder();
        $dto->roles = $entity->getRoles();

        return $dto;
    }

    public function toEntity(): User
    {
        $this->updateEntity($this->entity ?? new User());

        return $this->entity;
    }

    public function toEntityForValidation(): User
    {
        $entity = $this->entity ?? $this->instantiate(User::class);
        $entity->setUsername($this->username);

        return $entity;
    }

    public function updateEntity(User $entity): void
    {
        $entity->setUsername($this->username);

        if (!$this->password instanceof Placeholder) {
            $entity->setPassword($this->hasher->hashPassword($entity, $this->password));
        }

        $entity->setRoles($this->roles);
        $this->entity = $entity;
    }
}