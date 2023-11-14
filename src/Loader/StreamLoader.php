<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;

/**
 * @template Type of non-empty-array<array-key, mixed>|object
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

    /** @return \Generator<int<0, max>, AcceptanceResultBucket<Type>|EmptyResultBucket, Type|null, void> */
    public function load(): \Generator
    {
        $line = yield new EmptyResultBucket();
        /* @phpstan-ignore-next-line */
        while (true) {
            if (null === $line) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            fwrite($this->stream, $this->formatLine($line));
            $line = yield new AcceptanceResultBucket($line);
        }
    }

    /**
     * @param Type|null $line
     */
    abstract protected function formatLine(mixed $line): string;
}
