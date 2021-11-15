<?php

namespace App\Controller\Admin;

use App\Entity\Operation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Validator\Constraints\Choice;

class OperationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Operation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined()->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('operation')
            ->setEntityLabelInPlural('operations')
            ->setTimezone('UTC');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        $actions->remove(Crud::PAGE_INDEX, Action::EDIT);
        $actions->remove(Crud::PAGE_INDEX, Action::NEW);
        $actions->disable(Action::DELETE);
        $actions->remove(Crud::PAGE_DETAIL, Action::EDIT);
        $actions->remove(Crud::PAGE_DETAIL, Action::DELETE);
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('host')->setLabel('host'),
            TextField::new('message')->setLabel('message'),
            TextField::new('status')->setLabel('status'),
            DateTimeField::new('createdAt')->setLabel('created_at')->hideOnIndex(),
            DateTimeField::new('dispatchedAt')->setLabel('dispatched_at')->hideOnIndex(),
            DateTimeField::new('handledAt')->setLabel('handled_at')->hideOnIndex()
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)->add('host');
    }

}
