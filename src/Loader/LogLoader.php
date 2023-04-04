<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @template Type
 *
 * @template-implements LoaderInterface<Type>
 */
final readonly class LogLoader implements LoaderInterface
{
    public function __construct(private LoggerInterface $logger, private string $logLevel = LogLevel::DEBUG)
    {
    }

    /** @return \Generator<mixed, AcceptanceResultBucket<Type|null>|EmptyResultBucket, Type|null, void> */
    public function load(): \Generator
    {
        $line = yield new EmptyResultBucket();
        do {
            $this->logger->log($this->logLevel, var_export($line, true));
        } while ($line = yield new AcceptanceResultBucket($line));
    }
}
