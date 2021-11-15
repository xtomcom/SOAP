<?php

namespace App\Messenger;

use DateTimeInterface;
use Symfony\Component\Uid\AbstractUid;

class OperationSuccess implements OperationStatusInterface
{
    public function __construct(
        private AbstractUid       $operationId,
        private DateTimeInterface $handledAt
    )
    {
    }

    public function getOperationId(): AbstractUid
    {
        return $this->operationId;
    }

    public function getHandledAt(): DateTimeInterface
    {
        return $this->handledAt;
    }
}