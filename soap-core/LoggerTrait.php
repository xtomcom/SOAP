<?php

namespace xTom\SOAP;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait as BaseLoggerTrait;

trait LoggerTrait
{
    use LoggerAwareTrait;
    use BaseLoggerTrait;

    public function log($level, $message, array $context = [])
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message, $context);
        }
    }
}
