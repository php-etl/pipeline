<?php

namespace Kiboko\Component\Pipeline\Extractor;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\ExtractorInterface;

class IteratorExtractor implements ExtractorInterface
{
    /**
     * @var \Traversable
     */
    private $traversable;

    /**
     * @param \Traversable $traversable
     */
    public function __construct(\Traversable $traversable)
    {
        $this->traversable = $traversable;
    }

    public function extract(): void
    {
        foreach ($this->traversable as $line) {
            \Fiber::suspend(new AcceptanceResultBucket($line));
        }
    }
}
