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

    /** @return \Generator<mixed, AcceptanceResultBucket<Type>|EmptyResultBucket, null|Type, void> */
    public function transform(): \Generator
    {
        $line = yield new EmptyResultBucket();
        do {
        } while ($line = yield new AcceptanceResultBucket(($this->callback)($line)));
    }
}
