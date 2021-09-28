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

    /** @return \Generator<mixed, AcceptanceResultBucket<Type>|EmptyResultBucket, null|Type, void> */
    public function transform(): \Generator
    {
        $callback = $this->callback;

        $line = yield new EmptyResultBucket();
        while (true) {
            if ($line === null || !$callback($line)) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            $line = yield new AcceptanceResultBucket($line);
        }
    }
}
