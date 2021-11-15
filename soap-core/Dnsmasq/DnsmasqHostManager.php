<?php

namespace xTom\SOAP\Dnsmasq;

use Safe\Exceptions\FilesystemException;
use xTom\SOAP\Contracts\HostInterface;
use xTom\SOAP\Contracts\HostManagerInterface;
use xTom\SOAP\DhcpOption;
use xTom\SOAP\Exception\CommandFailedException;
use xTom\SOAP\Exception\HostManagerException;
use xTom\SOAP\ProcessCommand;
use function file_exists;
use function implode;
use function Safe\file_put_contents;
use function Safe\unlink;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

class DnsmasqHostManager implements HostManagerInterface
{
    public const TAG_PREFIX = 'host-';

    public function __construct(
        private string         $hostsDir,
        private string         $optionsDir,
        private ProcessCommand $reloadCommand
    )
    {
    }

    protected function getTag(string $configName): string
    {
        return self::TAG_PREFIX . $configName;
    }

    protected function getHostFilePathFromTag(string $tag): string
    {
        return $this->hostsDir . DIRECTORY_SEPARATOR . $tag;
    }

    protected function getHostFilePath(string $configName): string
    {
        return $this->getHostFilePathFromTag($this->getTag($configName));
    }

    protected function getOptionsFilePathFromTag(string $tag): string
    {
        return $this->optionsDir . DIRECTORY_SEPARATOR . $tag;
    }

    protected function getOptionsFilePath(string $configName): string
    {
        return $this->getOptionsFilePathFromTag($this->getTag($configName));
    }

    protected function getHostFileContent(HostInterface $host, string $tag): string
    {
        return implode(',', [
                $host->getMacAddress(),
                "set:$tag",
                $host->getIpAddress()
            ]) . "\n";
    }

    protected function getOptionsFileContent(HostInterface $host, string $tag)
    {
        $content = '';
        foreach ($host->getOptions() as $option) {
            $line = "tag:$tag,{$option->getTag()},{$option->getValue()}" . PHP_EOL;
            if (DhcpOption::BOOTFILE === $option->getTag()) {
                $line = "tag:iPXE,$line";
            }
            $content .= $line;
        }

        return $content;
    }

    public function register(HostInterface $host): void
    {
        $tag = $this->getTag($host->getConfigName());

        try {
            $path = $this->getHostFilePathFromTag($tag);
            file_put_contents($path, $this->getHostFileContent($host, $tag));

            $path = $this->getOptionsFilePathFromTag($tag);
            file_put_contents($path, $this->getOptionsFileContent($host, $tag));
        } catch (FilesystemException $e) {
            throw new HostManagerException("Failed to write $path: {$e->getMessage()}", $e->getCode(), $e);
        }

    }

    public function delete(string $configName): void
    {
        $paths = [
            $this->getHostFilePath($configName),
            $this->getOptionsFilePath($configName)
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                try {
                    unlink($path);
                } catch (FilesystemException $e) {
                    throw new HostManagerException("Failed to delete $path: {$e->getMessage()}", $e->getCode(), $e);
                }
            }
        }
    }

    public function reload(): void
    {
        try {
            $this->reloadCommand->run();
        } catch (CommandFailedException $e) {
            throw new HostManagerException("Failed to reload dnsmasq: {$e->getMessage()}", $e->getCode(), $e);
        }
    }
}