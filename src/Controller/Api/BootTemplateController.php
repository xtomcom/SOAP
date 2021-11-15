<?php

namespace App\Controller\Api;

use App\Controller\UlidTrait;
use App\Entity\BootTemplate;
use App\Entity\Host;
use App\Repository\BootTemplateRepository;
use App\Repository\HostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;

class BootTemplateController extends ApiController
{

    protected BootTemplateRepository|ObjectRepository|EntityRepository $repository;

    public function __construct(protected EntityManagerInterface $entityManager)
    {
        $this->repository = $this->entityManager->getRepository(BootTemplate::class);
    }

    #[Route(path: '/api/v1/bootTemplate', methods: ['GET'])]
    public function handleList()
    {
        $results = [];

        foreach ($this->repository->findAll() as $result)
        {
            $results[$result->getId()->__toString()] = $result->getName();
        }

        return $this->toResponse($results);
    }

    #[Route(path: '/api/v1/bootTemplate/{ulid}', methods: ['GET'])]
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

        return $this->toResponse([
            'id' => $ulid,
            'ipxeScript' => $result->getIpxeScript(),
            'preseed' => $result->getPreseed(),
        ]);
    }
}