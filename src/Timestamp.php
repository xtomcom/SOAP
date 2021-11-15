<?php

namespace App;

use DateTimeInterface;
use DateTimeZone;
use DateTimeImmutable;

/**
 * Date and time in UTC
 */
class Timestamp
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    private function __construct(
        private DateTimeImmutable $dateTime
    )
    {
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    public static function now()
    {
        return new self(
            new DateTimeImmutable('now', new DateTimeZone('UTC'))
        );
    }

    public static function fromDateTimeTz(DateTimeInterface $dateTime)
    {
        return new self(
            DateTimeImmutable::createFromInterface($dateTime)
                ->setTimezone(new DateTimeZone('UTC'))
        );
    }

    public static function fromDateTime(DateTimeInterface $dateTime)
    {
        return new self(
            DateTimeImmutable::createFromFormat(
                self::DATETIME_FORMAT,
                $dateTime->format(self::DATETIME_FORMAT),
                new DateTimeZone('UTC')
            )
        );
    }

    public function after(int $seconds): self
    {
        $timestamp = clone $this;
        $timestamp->dateTime = $this->dateTime->modify("$seconds sec");

        return $timestamp;
    }

    public function isLaterThan(Timestamp $timestamp) : bool
    {
        return $this->dateTime > $timestamp->dateTime;
    }
}