<?php

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

/** @template Type */
class CallableTransformer implements TransformerInterface
{
    /** @var callable */
    private $callback;

    /** @param callable $callback */
    public function __construct(
        callable $callback
    ) {
        $this->callback = $callback;
    }

    public function transform(): \Fiber
    {
        return new \Fiber(function () {
            $line = \Fiber::suspend(new EmptyResultBucket());
            do {} while ($line = \Fiber::suspend(new AcceptanceResultBucket(($this->callback)($line))));
        });
    }
}
