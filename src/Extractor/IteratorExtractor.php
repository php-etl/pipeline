<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Extractor;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\ExtractorInterface;

class IteratorExtractor implements ExtractorInterface
{
    public function __construct(private readonly \Traversable $traversable)
    {
    }

    /**
     * @return \Generator
     */
    public function extract(): iterable
    {
        yield from $this->traversable;
    }
}
