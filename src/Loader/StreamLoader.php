<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;

/**
 * @template Type
 *
 * @implements LoaderInterface<Type, Type>
 */
abstract class StreamLoader implements LoaderInterface
{
    /** @var resource */
    private $stream;

    /** @param resource $stream */
    public function __construct($stream)
    {
        if (!\is_resource($stream) || 'stream' !== get_resource_type($stream)) {
            throw new \InvalidArgumentException('Argument provided is not the valid type, please provide a stream resource.');
        }

        $this->stream = $stream;
    }

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

            fwrite($this->stream, $this->formatLine($line));
            /** @var AcceptanceResultBucket<Type> $bucket */
            $bucket = new AcceptanceResultBucket($line);
            $line = yield $bucket;
        }
    }

    /**
     * @param Type|null $line
     */
    abstract protected function formatLine(mixed $line): string;
}
