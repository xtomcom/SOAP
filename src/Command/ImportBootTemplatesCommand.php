<?php

namespace App\Command;

use App\Entity\BootTemplate;
use App\Repository\BootTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function file_exists;
use function Safe\file_get_contents;

#[AsCommand(
    name: 'app:import-boot-templates',
    description: 'Import boot templates',
)]
class ImportBootTemplatesCommand extends Command
{
    protected BootTemplateRepository|ObjectRepository|EntityRepository $repository;

    public function __construct(protected EntityManagerInterface $entityManager)
    {
        $this->repository = $this->entityManager->getRepository(BootTemplate::class);

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $finder
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->directories()
            ->in(__DIR__ . '/../../boot_templates')
            ->filter(function (SplFileInfo $dir): bool {
                if (
                    file_exists($dir->getRealPath() . '/boot.ipxe.twig') &&
                    file_exists($dir->getRealPath() . '/install.cfg.twig')
                ) {
                    return true;
                }

                return false;
            });
        foreach ($finder as $dir) {
            $name = $dir->getBasename();
            $template = $this->repository->findOneBy(['name' => $name]);
            if ($template === null) {
                $template = new BootTemplate();
                $template->setName($name);
                $template->setType('twig');
                $output->writeln('Insert ' . $name);
            } else {
                $output->writeln('Update ' . $name);
            }
            $template->setIpxeScript(file_get_contents($dir->getRealPath() . '/boot.ipxe.twig'));
            $template->setPreseed(file_get_contents($dir->getRealPath() . '/boot.ipxe.twig'));
            $this->entityManager->persist($template);

        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
