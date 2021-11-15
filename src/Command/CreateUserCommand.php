<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create admin user',
)]
class CreateUserCommand extends Command
{

    public function __construct(
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $hasher
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $this->addArgument(
            'username',
            InputArgument::REQUIRED,
            'The username of the user.'
        )->addArgument(
            'password',
            InputArgument::REQUIRED,
            'The password of the user.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setUsername($input->getArgument('username'));
        $user->setPassword($this->hasher->hashPassword($user, $input->getArgument('password')));
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->em->persist($user);
        $this->em->flush();

        return Command::SUCCESS;
    }
}
