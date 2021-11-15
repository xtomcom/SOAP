<?php

namespace App\Controller\Admin;

use App\Controller\UlidTrait;
use App\Entity\BootTemplate;
use App\Entity\Host;
use App\Entity\Operation;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractDashboardController
{
    use UlidTrait;

    public function __construct(
        private AdminUrlGenerator $urlGenerator,
        private TranslatorInterface $translator
    )
    {
    }

    #[Route('/', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('welcome.html.twig', [
            'title' => 'welcome',
            'create_host_link' => $this->urlGenerator
                ->setController(HostCrudController::class)
                ->setAction(Action::NEW),
            'login_link' => $this->generateUrl('security_login'),
            'logout_link' => $this->generateUrl('security_logout')
        ]);
    }

    #[Route('/host/{ulid}', name: 'host_link')]
    public function host(string $ulid): Response
    {
        $ulid = $this->validateUlid($ulid);

        return $this->redirect(
            $this->urlGenerator
                ->setController(HostCrudController::class)
                ->setEntityId($ulid)
                ->setAction(Action::DETAIL)
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->translator->trans('title'));
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToCrud('users', 'fas fa-users', User::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('boot_templates', 'fas fa-pencil-ruler', BootTemplate::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('hosts', 'fas fa-server', Host::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('operations', 'fas fa-project-diagram', Operation::class)
                ->setPermission('ROLE_ADMIN')
        ];
    }
}
