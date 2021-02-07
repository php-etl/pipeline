<?php

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogLoader implements LoaderInterface
{
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $logLevel;

    public function __construct(LoggerInterface $logger, string $logLevel = LogLevel::DEBUG)
    {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
    }

    public function load(): \Generator
    {
        $line = yield;
        do {
            $this->logger->log($this->logLevel, var_export($line, true));
        } while ($line = yield new AcceptanceResultBucket($line));
    }
}
