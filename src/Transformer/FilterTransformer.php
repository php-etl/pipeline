<?php

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

class FilterTransformer implements TransformerInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function transform(): \Generator
    {
        $callback = $this->callback;

        $line = yield;
        while (true) {
            if (!$callback($line)) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            $line = yield new AcceptanceResultBucket($line);
        }
    }
}
