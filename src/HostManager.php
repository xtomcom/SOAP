<?php

namespace App;

use App\Entity\Host;
use App\Entity\Operation;
use App\Repository\HostRepository;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Messenger\Reload;
use function count;

class HostManager
{

    protected HostRepository|ObjectRepository|EntityRepository $repository;

    public function __construct(
        protected MessageBusInterface    $messageBus,
        protected EntityManagerInterface $entityManager,
        protected HostOperationManager $operationManager
    )
    {
        $this->repository = $this->entityManager->getRepository(Host::class);
    }

    public function cron(): void
    {
        $count = 0;
        $hosts = $this->repository->findExpired();
        $count += count($hosts);
        foreach ($hosts as $host) {
            $this->operationManager->initDeletion($host);
        }
        $this->entityManager->flush();

        $hosts = $this->repository->findToDispatchDeletion();
        $count += count($hosts);
        foreach ($hosts as $host) {
            $host->getDeletion()->dispatch($this->messageBus);
        }
        $this->entityManager->flush();
        if (0 < $count) {
            $this->triggerReload();
        }
        $this->dispatchUnhandledOperations();
    }

    public function triggerReload(): void
    {
        $operation = new Operation();
        $operation->setHost(null);
        $operation->setMessage(new Reload());
        $this->entityManager->persist($operation);
        $operation->dispatch($this->messageBus);
        $this->entityManager->flush();
    }

    public function postDeletion(Operation $operation): void
    {
        if (null === $host = $operation->getHost()) {
            return;
        }
        $this->repository->deleteHost($host);
    }

    public function postRegistration(Operation $operation): void
    {
        if (null === $host = $operation->getHost()) {
            return;
        }
        $host->setRegistration(null);
        $this->entityManager->flush();
    }

    public function dispatchUnhandledOperations(): void
    {
        /** @var OperationRepository $repository */
        $repository = $this->entityManager->getRepository(Operation::class);

        foreach ($repository->findUnhandled() as $operation) {
            $operation->dispatch($this->messageBus);
        }
        $this->entityManager->flush();
    }
}
