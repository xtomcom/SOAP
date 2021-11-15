<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends AbstractController
{
    protected function toNotFound(): Response
    {
        return $this->toError('Not Found', 404);
    }

    protected function toError(string $message, int $status = 400): Response
    {
        return $this->toResponse([
            'errors' => [
                '_' => $message
            ]
        ], $status);
    }

    protected function toResponse(array $value, int $status = 200): Response
    {
        return new Response($this->encodeJson($value), $status, [
            'Content-Type' => 'application/json; charset=utf-8'
        ]);
    }

    protected function encodeJson(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}