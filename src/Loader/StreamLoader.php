<?php

namespace Kiboko\Component\Pipeline\Loader;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;

abstract class StreamLoader implements LoaderInterface
{
    /** @var resource */
    private $stream;

    /**
     * @param resource $stream
     */
    public function __construct($stream)
    {
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new \InvalidArgumentException(
                'Argument provided is not the valid type, please provide a stream resource.'
            );
        }

        $this->stream = $stream;
    }

    public function load(): \Generator
    {
        $line = yield;

        while (true) {
            fwrite($this->stream, $this->formatLine($line));

            $line = yield new AcceptanceResultBucket($line);
        }
    }

    abstract protected function formatLine($line);
}
