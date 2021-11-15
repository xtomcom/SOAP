<?php

namespace App\Command;

use App\HostManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:cron',
    description: 'Run cron job',
)]
class CronCommand extends Command
{
    public function __construct(protected HostManager $hostManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->hostManager->cron();

        return Command::SUCCESS;
    }
}
