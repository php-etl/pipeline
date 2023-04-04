<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;

/**
 * @template Type
 *
 * @template-implements LoaderInterface<Type>
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

    /** @return \Generator<mixed, AcceptanceResultBucket<Type|null>|EmptyResultBucket, Type|null, void> */
    public function load(): \Generator
    {
        $line = yield new EmptyResultBucket();
        while (true) {
            fwrite($this->stream, (string) $this->formatLine($line));
            $line = yield new AcceptanceResultBucket($line);
        }
    }

    abstract protected function formatLine($line);
}
