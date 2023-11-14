<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @template Type of non-empty-array<array-key, mixed>|object
 *
 * @implements LoaderInterface<Type, Type>
 */
final readonly class LogLoader implements LoaderInterface
{
    public function __construct(private LoggerInterface $logger, private string $logLevel = LogLevel::DEBUG)
    {
    }

    /** @return \Generator<positive-int, AcceptanceResultBucket<Type>|EmptyResultBucket, Type|null, void> */
    public function load(): \Generator
    {
        $line = yield new EmptyResultBucket();
        /** @phpstan-ignore-next-line */
        while (true) {
            if ($line === null) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            $this->logger->log($this->logLevel, var_export($line, true));
            $line = yield new AcceptanceResultBucket($line);
        }
    }
}
