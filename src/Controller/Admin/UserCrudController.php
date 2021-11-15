<?php

namespace App\Controller\Admin;

use App\Dto\UserDto;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends DtoCrudController
{
    public function __construct(protected UserPasswordHasherInterface $hasher)
    {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('user')
            ->setEntityLabelInPlural('users')
            ->showEntityActionsInlined()->setEntityPermission('ROLE_ADMIN')->setTimezone('UTC');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        $actions->setPermissions([
            Action::NEW => 'ROLE_ADMIN'
        ]);
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from [
            IdField::new('id')->setLabel('id')->setMaxLength(26)->onlyOnDetail(),
            TextField::new('username')->setLabel('username')->setRequired(false),
            ArrayField::new('roles')->setLabel('roles'),
        ];

        switch ($pageName) {
            case Crud::PAGE_NEW:
                yield TextField::new('password', 'Password')->setLabel('password')
                    ->setFormType(PasswordType::class);
                break;

            case Crud::PAGE_EDIT:
                yield TextField::new('password', 'Password')->setLabel('password')
                    ->setFormType(PasswordType::class)
                    ->setRequired(false)
                    ->setHelp('Leave the field blank to remain the password unchanged');
                break;
        }
    }

    public function getDtoFqcn(): string
    {
        return UserDto::class;
    }

    public function createEntity(string $entityFqcn)
    {
        return new UserDto($this->hasher);
    }

    public function createDtoFromEntity(object $entity): object
    {
        /** @var User $entity */
        return UserDto::fromEntity($entity, $this->hasher);
    }
}
