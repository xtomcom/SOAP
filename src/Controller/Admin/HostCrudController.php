<?php

namespace App\Controller\Admin;

use App\Booting\DelegatingTemplate;
use App\Dto\HostDto;
use App\Entity\Host;
use App\HostManager;
use App\HostOperationManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

class HostCrudController extends DtoCrudController
{
    public const DELETE_ACTION = 'triggerDeletion';
    public const LINK_ACTION = 'link';

    public function __construct(
        protected MessageBusInterface    $messageBus,
        protected EntityManagerInterface $entityManager,
        protected HostManager            $hostManager,
        protected HostOperationManager   $operationManager,
        protected DelegatingTemplate     $template
    )
    {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setTimezone('UTC')
            ->showEntityActionsInlined()
            ->overrideTemplates([
                'crud/field/url' => 'field/url.html.twig',
                'crud/field/code_editor' => 'field/code_editor.html.twig'
            ])
            ->setEntityLabelInSingular('host')
            ->setEntityLabelInPlural('hosts');
    }

    public static function getEntityFqcn(): string
    {
        return Host::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $isNotPendingUpdate = static fn(Host $host) => !$host->isPendingDeletion() && !$host->isPendingRegistration();
        $actions = parent::configureActions($actions);

        $linkAction = Action::new(self::LINK_ACTION)->linkToUrl(function (Host $host) {
            return $this->generateUrl('host_link', [
                'ulid' => $host->getId()->__toString()
            ]);
        });
        $actions->add(Crud::PAGE_DETAIL, $linkAction)->add(Crud::PAGE_INDEX, $linkAction);
        $actions->getAsDto(Crud::PAGE_INDEX)
            ->getAction(Crud::PAGE_INDEX, Action::EDIT)
            ->setDisplayCallable($isNotPendingUpdate);
        $actions->getAsDto(Crud::PAGE_DETAIL)
            ->getAction(Crud::PAGE_DETAIL, Action::EDIT)
            ->setDisplayCallable($isNotPendingUpdate);
        $actions->disable(Action::DELETE);
        $deleteHostIndex = Action::new(static::DELETE_ACTION, 'delete_action')
            ->linkToCrudAction(static::DELETE_ACTION)
            ->displayIf($isNotPendingUpdate)
            ->setCssClass('link-danger');
        $deleteHostDetail = Action::new(static::DELETE_ACTION, 'delete_action')
            ->linkToCrudAction(static::DELETE_ACTION)
            ->displayIf($isNotPendingUpdate)
            ->setCssClass('btn btn-danger');
        $actions->add(Crud::PAGE_INDEX, $deleteHostIndex);
        $actions->add(Crud::PAGE_DETAIL, $deleteHostDetail);

        $actions->setPermissions([
            Action::INDEX => 'ROLE_ADMIN'
        ]);
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        if (null === $this->getUser()) {
            $expiresAfter = ChoiceField::new('expiresAfter')
                ->setLabel('expires_after')
                ->setChoices(HostDto::EXPIRES_AFTER)
                ->onlyOnForms()
                ->renderExpanded(true)
                ->setRequired(true)
                ->hideWhenUpdating();
        } else {
            $expiresAfter = ChoiceField::new('expiresAfter')
                ->setLabel('expires_after')
                ->setChoices(HostDto::EXPIRES_AFTER)
                ->onlyOnForms()
                ->renderExpanded(true)
                ->setRequired(false);
        }

        yield from [
            IdField::new('id')
                ->setLabel('id')
                ->setMaxLength(26)
                ->onlyOnDetail(),
            TextField::new('hostname')
                ->setLabel('hostname'),
            TextField::new('macAddress')
                ->setLabel('mac_address')
                ->hideWhenUpdating(),
            TextField::new('ipAddress')
                ->setLabel('ip_address')
                ->hideWhenUpdating(),
            ChoiceField::new('prefix')
                ->setLabel('prefix')
                ->setChoices(HostDto::NETMASKS)
                ->hideOnIndex()
                ->setRequired(true),
            TextField::new('gateway')
                ->setLabel('gateway')
                ->hideOnIndex(),
            ArrayField::new('dns')
                ->setLabel('dns')
                ->hideOnIndex(),
            AssociationField::new('registration')
                ->setLabel('registration')
                ->hideOnForm(),
            AssociationField::new('deletion')
                ->setLabel('deletion')
                ->hideOnForm(),
            IntegerField::new('reachesToExpire')
                ->setLabel('reaches_to_expire')
                ->hideOnForm()
                ->hideOnIndex(),
            $expiresAfter,
            DateTimeField::new('expiresAt')
                ->setLabel('expires_at')
                ->hideOnForm(),
            AssociationField::new('bootTemplate')
                ->setLabel('boot_template')
                ->addJsFiles('js/boot_template.js')
        ];


        switch ($pageName) {
            case Crud::PAGE_NEW:
            case Crud::PAGE_EDIT:
                yield TextField::new('rootPassword', 'root_password')
                    ->setFormType(PasswordType::class)
                    ->setRequired(false)
                    ->setHelp('root_password_help');
                yield from [
                    FormField::addPanel('boot_scripts')->setHelp('boot_scripts_help'),
                    CodeEditorField::new('ipxeScript')
                        ->setLabel('ipxe_script')
                        ->setLanguage('twig'),
                    CodeEditorField::new('preseed')
                        ->setLabel('install_config')
                        ->setLanguage('twig')
                        ->setHelp('install_config_help')
                ];
                break;

            case Crud::PAGE_DETAIL:
                yield CodeEditorField::new('rootPassword', 'root_password');
                yield from [
                    FormField::addPanel('URLs'),
                    UrlField::new('linkUrl')
                        ->setLabel('link_url')
                        ->formatValue(
                        function ($str, $host) {
                            return $this->generateUrl('host_link', [
                                'ulid' => $host->getId()->__toString()
                            ],
                                UrlGenerator::ABSOLUTE_URL);
                        }
                    ),
                    UrlField::new('ipxeScriptUrl')
                        ->setLabel('ipxe_script_url')
                        ->formatValue(
                        function ($str, $host) {
                            return 'http:' . $this->generateUrl('ipxe_script', [
                                    'ulid' => $host->getId()->__toString()
                                ],
                                    UrlGenerator::NETWORK_PATH);
                        }
                    ),

                    UrlField::new('preseedUrl')
                        ->setLabel('install_config_url')
                        ->formatValue(
                        function ($str, $host) {
                            return 'http:' . $this->generateUrl('install_config', [
                                    'ulid' => $host->getId()->__toString()
                                ],
                                    UrlGenerator::NETWORK_PATH);
                        }
                    ),
                    FormField::addPanel('boot_scripts')->setHelp('boot_scripts_help'),
                    CodeEditorField::new('ipxeScriptRendered')
                        ->setLabel('ipxe_script')
                        ->formatValue(
                        function ($str, $host) {
                            return $this->template->getIpxeScript($host, $this->generateUrl(
                                'install_config',
                                ['ulid' => $host->getId()->__toString()],
                                UrlGenerator::ABSOLUTE_URL
                            ));
                        }
                    )->setLanguage('twig'),
                    CodeEditorField::new('preseedRendered')
                        ->setLabel('install_config')
                        ->formatValue(
                        function ($str, $host) {
                            return $this->template->getPreseed($host);
                        }
                    )->setLanguage('twig'),


                ];
                break;
        }

    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters = parent::configureFilters($filters);

        return $filters;
    }

    public function getDtoFqcn(): string
    {
        return HostDto::class;
    }

    /**
     * Dispatch host registration
     */
    public function convertDtoToEntity(BeforeEntityPersistedEvent $event): void
    {
        parent::convertDtoToEntity($event);
        $host = $event->getEntityInstance();
        if (!$host instanceof Host) {
            return;
        }
        $operation = $this->operationManager->initRegistration($host);
        $operation->dispatch($this->messageBus);
    }

    public function convertDtoToUpdatedEntity(BeforeEntityUpdatedEvent $event): void
    {
        parent::convertDtoToUpdatedEntity($event);
        $host = $event->getEntityInstance();
        if (!$host instanceof Host) {
            return;
        }
        $operation = $this->operationManager->initRegistration($host);
        $operation->dispatch($this->messageBus);
    }

    public function triggerDeletion(AdminContext $context): Response
    {
        /** @var Host $host */
        $host = $context->getEntity()->getInstance();
        $this->operationManager->initDeletion($host);
        $this->entityManager->flush();
        $url = $context->getReferrer() ?? $this->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl();

        return $this->redirect($url);
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];
        if (Action::SAVE_AND_RETURN === $submitButtonName) {
            $url = $this->get(AdminUrlGenerator::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                ->generateUrl();

            return $this->redirect($url);
        }
        return parent::getRedirectResponseAfterSave($context, $action);
    }
}
