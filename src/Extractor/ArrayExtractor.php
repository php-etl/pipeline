<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Extractor;

use Kiboko\Contract\Pipeline\ExtractorInterface;

/**
 * @template Type
 * @implements ExtractorInterface<Type>
 */
class ArrayExtractor implements ExtractorInterface
{
    /** @param non-empty-array<array-key, Type> $data */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * @return \Generator
     */
    public function extract(): iterable
    {
        yield from $this->data;
    }
}
