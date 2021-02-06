<?php

namespace Kiboko\Component\Pipeline\Extractor;

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

    /**
     * @return \Generator
     */
    public function extract(): iterable
    {
        yield from $this->traversable;
    }
}
