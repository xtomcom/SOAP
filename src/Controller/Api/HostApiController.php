<?php

namespace App\Controller\Api;

use App\Dto\HostDto;
use App\Entity\BootTemplate;
use App\Entity\Host;
use App\Entity\Operation;
use App\HostOperationManager;
use App\Repository\HostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function json_decode;
use const JSON_THROW_ON_ERROR;

class HostApiController extends ApiController
{
    protected HostRepository|ObjectRepository|EntityRepository $repository;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected HostOperationManager   $operationManager,
        protected MessageBusInterface    $messageBus,
        protected DenormalizerInterface  $denormalizer,
        protected ValidatorInterface     $validator
    )
    {
        $this->repository = $this->entityManager->getRepository(Host::class);
    }


    protected function validateRequestData(string $data, string $model, $entity = null)
    {
        $jsonData = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        if (null === $jsonData) {
            return $this->toError('Invalid JSON');
        }

        $bootTemplate = null;
        if (isset($jsonData['bootTemplate'])) {
            $bootTemplate = $jsonData['bootTemplate'];
            if (!Ulid::isValid($bootTemplate)) {
                return $this->toResponse([
                    'errors' => [
                        'bootTemplate' => 'Invalid ID'
                    ]
                ], 400);
            }

            $bootTemplate = $this->entityManager->getRepository(BootTemplate::class)->findOneBy([
                'id' => new Ulid($bootTemplate)
            ]);

            if ($bootTemplate === null) {
                return $this->toResponse([
                    'errors' => [
                        'bootTemplate' => 'Not Found'
                    ]
                ], 400);
            }
            unset($jsonData['bootTemplate']);
        }

        $context = [];
        if ($entity !== null) {
            $context = [AbstractNormalizer::OBJECT_TO_POPULATE => $entity];
        }

        try {
            $data = $this->denormalizer->denormalize($jsonData, $model, 'json', $context);
        } catch (Exception $e) {
            return $this->toError($e::class . ': ' . $e->getMessage());
        }
        if ($bootTemplate !== null) {
            $data->bootTemplate = $bootTemplate;
        }
        $errors = $this->validator->validate($data);
        if ($errors->count() > 0) {
            return $this->toResponse($this->violationsToErrors($errors), 400);
        }

        return $data;
    }

    private function violationsToErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        /** @var ConstraintViolation $violation */
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return ['errors' => $errors];
    }


    #[Route(path: '/api/v1/host/{ulid}', methods: ['GET'])]
    public function handleGet(string $ulid)
    {
        if (!Ulid::isValid($ulid)) {
            return $this->toNotFound();
        }

        $result = $this->repository->findOneBy([
            'id' => new Ulid($ulid)
        ]);

        if ($result === null) {
            return $this->toNotFound();
        }

        $dto = HostDto::fromEntity($result);

        return $this->toResponse([
            'id' => $ulid,
            'hostname' => $dto->hostname,
            'macAddress' => $dto->macAddress,
            'ipAddress' => $dto->ipAddress,
            'prefix' => $dto->prefix,
            'gateway' => $dto->gateway,
            'dns' => $dto->dns,
            'registration' => $result->isPendingRegistration() ? $this->renderOperation($result->isPendingRegistration()) : null,
            'deletion' => $result->isPendingDeletion() ? $this->renderOperation($result->getDeletion()) : null,
            'reachesToExpire' => $result->getReachesToExpire(),
            'expiresAt' => $result->getExpiresAt()->format('Y-m-d H:i:s'),
            'bootTemplate' => $result->getBootTemplate()->getId()->__toString(),
            'rootPassword' => $result->getRootPassword()
        ]);
    }

    #[Route(path: '/api/v1/host', methods: ['POST', 'PUT'])]
    public function create(Request $request)
    {
        /** @var $data HostDto */
        $data = $this->validateRequestData($request->getContent(), HostDto::class);

        if ($data instanceof Response) {
            return $data;
        }

        if ($data->expiresAfter === null) {
            $data->expiresAfter = 86400;
        }

        $data = $data->toEntity();
        $operation = $this->operationManager->initRegistration($data);
        $operation->dispatch($this->messageBus);
        $this->entityManager->persist($operation);
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $this->toResponse([
            'id' => $data->getId()->__toString(),
            'registration' => $this->renderOperation($operation),
            'rootPassword' => $data->getRootPassword()
        ], 201);
    }

    #[Route(path: '/api/v1/host/{ulid}', methods: ['POST', 'PUT'])]
    public function edit(string $ulid, Request $request)
    {
        if (!Ulid::isValid($ulid)) {
            return $this->toNotFound();
        }

        $result = $this->repository->findOneBy([
            'id' => new Ulid($ulid)
        ]);

        if ($result === null) {
            return $this->toNotFound();
        }

        /** @var $data HostDto */
        $data = $this->validateRequestData($request->getContent(), HostDto::class, HostDto::fromEntity($result));

        if ($data instanceof Response) {
            return $data;
        }

        $data->updateEntity($result);

        $operation = $this->operationManager->initRegistration($result);
        $operation->dispatch($this->messageBus);
        $this->entityManager->persist($operation);
        $this->entityManager->flush();

        return $this->toResponse([
            'id' => $result->getId()->__toString(),
            'registration' => $this->renderOperation($operation),
            'rootPassword' => $result->getRootPassword()
        ]);
    }

    #[Route(path: '/api/v1/host/{ulid}', methods: ['DELETE'])]
    public function delete(string $ulid)
    {
        if (!Ulid::isValid($ulid)) {
            return $this->toNotFound();
        }

        $result = $this->repository->findOneBy([
            'id' => new Ulid($ulid)
        ]);

        if ($result === null) {
            return $this->toNotFound();
        }

        $status = 200;
        if (!$result->isPendingDeletion()) {
            $status = 202;
            $operation = $this->operationManager->initDeletion($result);
            $this->entityManager->persist($operation);
            $this->entityManager->flush();
        }

        $operation = $result->getDeletion();
        return $this->toResponse([
            'id' => $ulid,
            'deletion' => $this->renderOperation($operation),
        ], $status);
    }

    private function renderOperation(Operation $operation)
    {
        return [
            'id' => $operation->getId()->__toString(),
            'dispatchedAt' => $operation->getDispatchedAt()?->format('Y-m-d H:i:s'),
            'handleAt' => $operation->getHandledAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
