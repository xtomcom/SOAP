<?php

namespace App\Entity;

use App\Messenger\OperationIdStamp;
use App\Repository\OperationRepository;
use App\Timestamp;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Uid\Ulid;

#[ORM\Table('operations')]
#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    use UlidTrait;

    public function __construct()
    {
        $this->id = new Ulid();
    }

    #[ORM\Column(type: 'timestamp', nullable: true)]
    private ?Timestamp $dispatchedAt = null;

    public function getDispatchedAt(): ?DateTimeImmutable
    {
        return $this->dispatchedAt?->toDateTime();
    }

    public function setDispatchedAt(?DateTimeInterface $dispatchedAt): static
    {
        $this->dispatchedAt = $dispatchedAt === null ?
            $dispatchedAt :
            Timestamp::fromDateTimeTz($dispatchedAt);

        return $this;
    }

    #[ORM\Column(type: 'timestamp', nullable: true)]
    private ?Timestamp $handledAt = null;

    public function getHandledAt(): ?DateTimeImmutable
    {
        return $this->handledAt?->toDateTime();
    }

    public function setHandledAt(?DateTimeInterface $handledAt): static
    {
        $this->handledAt = $handledAt === null ?
            $handledAt :
            Timestamp::fromDateTimeTz($handledAt);

        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Host::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Host $host = null;

    public function getHost(): ?Host
    {
        return $this->host;
    }

    public function setHost(?Host $host): static
    {
        $this->host = $host;

        return $this;
    }

    #[ORM\Column(type: 'object')]
    private object $message;

    public function getMessage(): object
    {
        return $this->message;
    }

    public function setMessage(object $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function dispatch(MessageBusInterface $messageBus): Envelope
    {
        $envelope = new Envelope($this->message, [new OperationIdStamp($this)]);
        $envelope = $messageBus->dispatch($envelope);
        if (null !== $envelope->last(SentStamp::class)) {
            $this->setDispatchedAt(new DateTimeImmutable());
        }
        return $envelope;
    }

    public function getStatus(): string
    {
        if (null !== $this->handledAt) {
            return 'Finished';
        } else if (null !== $this->dispatchedAt) {
            return 'Queued';
        } else {
            return 'Pending';
        }
    }

    public function __toString(): string
    {
        return $this->getStatus();
    }
}
