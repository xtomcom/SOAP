<?php

namespace App\Messenger;

use App\Entity\Operation;
use App\HostManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Messenger\HostDeletion;
use App\Messenger\HostRegistration;
use function get_class;

class OperationSuccessHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HostManager $hostManager
    )
    {
    }

    public function __invoke(OperationSuccess $message)
    {
        $repository = $this->entityManager->getRepository(Operation::class);
        /** @var Operation $operation */
        $operation = $repository->find($message->getOperationId());
        if ($operation === null) {
            return;
        }
        $operation->setHandledAt($message->getHandledAt());
        $this->entityManager->flush();
        switch (get_class($operation->getMessage())) {
            case HostRegistration::class:
                $this->hostManager->postRegistration($operation);
                break;
            case HostDeletion::class:
                $this->hostManager->postDeletion($operation);
                break;
        }
    }
}