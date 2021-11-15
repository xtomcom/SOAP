<?php

namespace xTom\SOAP;

use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Process\Process;
use xTom\SOAP\Exception\CommandFailedException;

class ProcessCommand implements LoggerAwareInterface
{
    use LoggerTrait;

    public function __construct(
        private array   $command,
        private ?string $cwd = null,
        private ?array  $env = null,
        private         $input = null,
        private ?float  $timeout = 60.0
    )
    {
    }

    public function run(): array
    {
        $process = new Process($this->command, $this->cwd, $this->env, $this->input, $this->timeout);
        $process->run();

        $context = [
            'pid' => $process->getPid(),
            'exitCode' => $exitCode = $process->getExitCode(),
            'exitCodeText' => $exitCodeText = $process->getExitCodeText(),
            'stdout' => $process->getOutput(),
            'stderr' => $stderr = $process->getErrorOutput(),
            'command' => $process->getCommandLine(),
            'cwd' => $this->cwd,
            'env' => $process->getEnv(),
            'timeout' => $process->getTimeout()
        ];

        if (!$process->isSuccessful()) {
            $message = "$exitCodeText: " . explode(PHP_EOL, $stderr, 1)[0];
            $this->error($message, $context);

            throw new CommandFailedException($message, $exitCode);
        }

        $this->debug('Command succeeded.', $context);

        return $context;
    }
}