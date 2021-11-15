<?php

namespace App\Messenger;

use App\Entity\Operation;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Uid\AbstractUid;

class OperationIdStamp implements StampInterface
{
    private AbstractUid $operationId;

    public function __construct(Operation $operation)
    {
        $this->operationId = $operation->getId();
    }

    public function getOperationId(): AbstractUid
    {
        return $this->operationId;
    }
}
