<?php

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

class CallableTransformer implements TransformerInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(
        callable $callback
    ) {
        $this->callback = $callback;
    }

    public function transform(): \Generator
    {
        $line = yield;
        do {
        } while ($line = yield new AcceptanceResultBucket(($this->callback)($line)));
    }
}
