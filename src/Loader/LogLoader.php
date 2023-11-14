<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @template Type
 *
 * @implements LoaderInterface<Type, Type>
 */
final readonly class LogLoader implements LoaderInterface
{
    public function __construct(private LoggerInterface $logger, private string $logLevel = LogLevel::DEBUG) {}

    /** @return \Generator<int, ResultBucketInterface<Type>, Type|null, void> */
    public function load(): \Generator
    {
        /** @var EmptyResultBucket<Type> $bucket */
        $bucket = new EmptyResultBucket();
        $line = yield $bucket;
        /* @phpstan-ignore-next-line */
        while (true) {
            if (null === $line) {
                /** @var EmptyResultBucket<Type> $bucket */
                $bucket = new EmptyResultBucket();
                $line = yield $bucket;
                continue;
            }

            $this->logger->log($this->logLevel, var_export($line, true));
            /** @var AcceptanceResultBucket<Type> $bucket */
            $bucket = new AcceptanceResultBucket($line);
            $line = yield $bucket;
        }
    }
}
