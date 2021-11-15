<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;
use Throwable;

trait UlidTrait
{
    abstract protected function createNotFoundException(string $message = 'Not Found', Throwable $previous = null): NotFoundHttpException;

    private function validateUlid(string $ulid) : Ulid
    {
        if (!Ulid::isValid($ulid)) {
            throw $this->createNotFoundException();
        }

        return new Ulid($ulid);
    }
}