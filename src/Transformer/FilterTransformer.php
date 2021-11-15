<?php

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template Type
 * @template-implements TransformerInterface<Type>
 */
class FilterTransformer implements TransformerInterface
{
    /** @var callable */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function transform(): \Fiber
    {
        $callback = $this->callback;

        $line = \Fiber::suspend(new EmptyResultBucket());
        while (true) {
            if ($line === null || !$callback($line)) {
                $line = \Fiber::suspend(new EmptyResultBucket());
                continue;
            }

            $line = \Fiber::suspend(new AcceptanceResultBucket($line));
        }
    }
}
