<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Extractor;

use Kiboko\Contract\Pipeline\ExtractorInterface;

/**
 * @template Type of non-empty-array<array-key, mixed>|object
 * @implements ExtractorInterface<Type>
 */
class IteratorExtractor implements ExtractorInterface
{
    /** @param \Traversable<mixed, Type> $traversable */
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
