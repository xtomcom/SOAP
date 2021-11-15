<?php

namespace App\Controller\Admin;

use Closure;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;

abstract class DtoCrudController extends AbstractCrudController implements EventSubscriberInterface
{
    protected $entityToEdit;

    abstract public function getDtoFqcn(): string;

    public function convertDtoToEntity(BeforeEntityPersistedEvent $event): void
    {
        $dto = $event->getEntityInstance();
        if (get_class($dto) !== $this->getDtoFqcn()) {
            return;
        }

        $entity = $this->createEntityFromDto($dto);
        Closure::bind(function ($entity): void {
            $this->entityInstance = $entity;
        }, $event, BeforeEntityPersistedEvent::class)($entity);
    }

    public function convertEntityToDto(BeforeCrudActionEvent $event): void
    {
        $context = $event->getAdminContext();
        if ($context->getCrud()->getCurrentAction() !== Action::EDIT) {
            return;
        }

        $entityDto = $context->getEntity();
        $entity = $entityDto->getInstance();
        if ($entity === null || get_class($entity) !== $this->getEntityFqcn()) {
            return;
        }

        // Hack: This triggers the primary key value to be cached on the entityDto. This is required because we
        // won't have access to the actual entity with it's pkey from the entityDto.
        $entityDto->getPrimaryKeyValue();

        $this->entityToEdit = $entity;
        $dto = $this->createDTOFromEntity($entity);
        Closure::bind(function ($dto): void {
            $this->instance = $dto;
        }, $entityDto, EntityDto::class)($dto);
    }

    public function convertDtoToUpdatedEntity(BeforeEntityUpdatedEvent $event): void
    {
        $dto = $event->getEntityInstance();
        if (get_class($dto) !== $this->getDtoFqcn()) {
            return;
        }

        if (null === $entity = $this->entityToEdit) {
            throw new RuntimeException('No entity to edit');
        }

        $this->updateEntityWithDto($entity, $dto);
        Closure::bind(function ($entity) {
            $this->entityInstance = $entity;
        }, $event, BeforeEntityUpdatedEvent::class)($entity);
    }

    /**
     * The entityId query param will get left in the generated urls when you click around the admin. For example, if you visit
     * show, and then hit the Back To Listings button, the url will have the recently shown entityId still in the url.
     * When creating a new entity, it causes the admin context provider to try and load the old entity by id and set it on the
     * EntityDto. This then causes an exception when we later try to setInstance on the EntityDto to a Dto class and not the
     * Entity.
     */
    public function removeEntityFromContext(BeforeCrudActionEvent $event): void
    {
        $context = $event->getAdminContext();
        if ($context->getCrud()->getCurrentAction() !== Action::NEW) {
            return;
        }

        $entityDto = $context->getEntity();
        Closure::bind(function () {
            $this->instance = null;
        }, $entityDto, EntityDto::class)();
    }

    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        $form = parent::createNewForm($entityDto, $formOptions, $context);
        $entityDto = $context->getEntity();
        Closure::bind(function (): void {
            $this->instance = null;
        }, $entityDto, EntityDto::class)();

        return $form;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => [['convertDtoToEntity']],
            BeforeCrudActionEvent::class => [['convertEntityToDto'], ['removeEntityFromContext']],
            BeforeEntityUpdatedEvent::class => [['convertDtoToUpdatedEntity']],
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        return new ($this->getDtoFqcn())();
    }

    public function createEntityFromDto(object $dto): object
    {
        return $dto->toEntity();
    }

    public function createDtoFromEntity(object $entity): object
    {
        return [$this->getDtoFqcn(), 'fromEntity']($entity);
    }

    public function updateEntityWithDto(object $entity, object $dto): void
    {
        $dto->updateEntity($entity);
    }
}