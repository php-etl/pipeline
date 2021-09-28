<?php

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;

/**
 * @template Type
 * @template-implements LoaderInterface<Type>
 */
abstract class StreamLoader implements LoaderInterface
{
    /** @var resource */
    private $stream;

    /** @param resource $stream */
    public function __construct($stream)
    {
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new \InvalidArgumentException(
                'Argument provided is not the valid type, please provide a stream resource.'
            );
        }

        $this->stream = $stream;
    }

    /** @return \Generator<mixed, AcceptanceResultBucket<Type|null>|EmptyResultBucket, null|Type, void> */
    public function load(): \Generator
    {
        $line = yield new EmptyResultBucket();
        while (true) {
            fwrite($this->stream, $this->formatLine($line));
            $line = yield new AcceptanceResultBucket($line);
        }
    }

    abstract protected function formatLine($line);
}
