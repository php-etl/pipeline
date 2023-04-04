<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Extractor;

use Kiboko\Contract\Pipeline\ExtractorInterface;

class ArrayExtractor implements ExtractorInterface
{
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
