<?php

namespace App\Messenger;

use DateTimeImmutable;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class OperationStatusMiddleware implements MiddlewareInterface
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $stack->next()->handle($envelope, $stack);
        if (null === $envelope->last(HandledStamp::class)) {
            return $envelope;
        }
        if (null === $stamp = $envelope->last(OperationIdStamp::class)) {
            return $envelope;
        }
        /** @var OperationIdStamp $stamp */
        $operationId = $stamp->getOperationId();
        $message = new OperationSuccess($operationId, new DateTimeImmutable());
        $this->messageBus->dispatch($message);

        return $envelope;
    }
}