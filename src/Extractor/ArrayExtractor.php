<?php

namespace Kiboko\Component\Pipeline\Extractor;

use Kiboko\Contract\Pipeline\ExtractorInterface;

class ArrayExtractor implements ExtractorInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return \Generator
     */
    public function extract(): iterable
    {
        yield from $this->data;
    }
}
