<?php

namespace App\Controller\Admin;

use App\Dto\BootTemplateDto;
use App\Entity\BootTemplate;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BootTemplateCrudController extends DtoCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->showEntityActionsInlined()
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('boot_template')
            ->setEntityLabelInPlural('boot_templates');
    }

    public static function getEntityFqcn(): string
    {
        return BootTemplate::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->setLabel('id')
                ->setMaxLength(26)
                ->hideOnForm(),
            TextField::new('name')
                ->setLabel('name'),
            ChoiceField::new('type')
                ->setLabel('type')
                ->setChoices(BootTemplateDto::TYPES),
            FormField::addPanel('twig_templates'),
            CodeEditorField::new('ipxeScript')
                ->setLabel('ipxe_script')
                ->setLanguage('twig'),
            CodeEditorField::new('preseed')
                ->setLabel('install_config')
                ->setHelp('install_config_help')
                ->setLanguage('twig')
        ];
    }

    public function getDtoFqcn(): string
    {
        return BootTemplateDto::class;
    }
}
