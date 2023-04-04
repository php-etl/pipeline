<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Component\Metadata\Type;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template Type
 *
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

    /**
     * @return \Generator<mixed, AcceptanceResultBucket<Type|mixed>|EmptyResultBucket, Type|null, void>
     */
    public function transform(): \Generator
    {
        $callback = $this->callback;

        $line = yield new EmptyResultBucket();
        while (true) {
            if (null === $line || !$callback($line)) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            $line = yield new AcceptanceResultBucket($line);
        }
    }
}
