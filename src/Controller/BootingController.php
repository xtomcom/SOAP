<?php

namespace App\Controller;

use App\Booting\DelegatingTemplate;
use App\Entity\Host;
use App\Repository\HostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use function filter_var;
use const FILTER_FLAG_IPV4;
use const FILTER_VALIDATE_IP;

class BootingController extends AbstractController implements ServiceSubscriberInterface
{
    use UlidTrait;

    protected HostRepository|ObjectRepository|EntityRepository $repository;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected DelegatingTemplate     $template
    )
    {
        $this->repository = $this->entityManager->getRepository(Host::class);
    }

    #[Route('/host/{ulid}/boot.ipxe', name: 'ipxe_script')]
    public function ipxeScript(string $ulid, Request $request): Response
    {
        $host = $this->getHost($ulid);
        $ipxeScript = $this->template->getIpxeScript(
            $host,
            $this->generateUrl(
                'install_config',
                ['ulid' => $host->getId()->__toString()],
                UrlGenerator::ABSOLUTE_URL
            )
        );

        if ('' === $this->template->getPreseed($host)) {
            $this->countReach($host, $request);
        }

        return new Response($ipxeScript, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    #[Route('/host/{ulid}/install.cfg', name: 'install_config')]
    public function installConfig(string $ulid, Request $request): Response
    {
        $host = $this->getHost($ulid);
        $preseed = $this->template->getPreseed($host);
        if ('' !== $preseed) {
            $this->countReach($host, $request);
        }

        return new Response($preseed, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    protected function getHost(string $ulid): Host
    {
        $ulid = $this->validateUlid($ulid);
        $host = $this->repository->findOneBy(['id' => $ulid]);
        if (null === $host) {
            throw $this->createNotFoundException();
        }
        return $host;
    }

    protected function countReach(Host $host, Request $request): void
    {
        $ip = filter_var($request->getClientIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        if (false === $ip || $host->getIpAddress()->toString() !== $ip) {
            return;
        }
        if (null !== $reachesToExpire = $host->getReachesToExpire()) {
            if ($reachesToExpire <= 0) {
                throw $this->createNotFoundException();
            } else if ($reachesToExpire === 1) {
                $host->setExpiresAfter(600);
                $this->entityManager->flush();
            }
            $this->repository->countReach($host);
        }
    }
}
