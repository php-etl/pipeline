<?php

namespace Kiboko\Component\Pipeline\Extractor;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
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

    public function extract(): void
    {
        foreach ($this->data as $line) {
            \Fiber::suspend(new AcceptanceResultBucket($line));
        }
    }
}
