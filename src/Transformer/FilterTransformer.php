<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template Type
 *
 * @implements TransformerInterface<Type, Type>
 */
class FilterTransformer implements TransformerInterface
{
    /** @var callable(Type): bool */
    private $callback;

    /** @param callable(Type $item): bool $callback */
    public function __construct(
        callable $callback,
    ) {
        $this->callback = $callback;
    }

    /**
     * @return \Generator<int, ResultBucketInterface<Type>, Type|null, void>
     */
    public function transform(): \Generator
    {
        $callback = $this->callback;

        /** @var EmptyResultBucket<Type> $bucket */
        $bucket = new EmptyResultBucket();
        $line = yield $bucket;
        /* @phpstan-ignore-next-line */
        while (true) {
            if (null === $line || !$callback($line)) {
                /** @var EmptyResultBucket<Type> $bucket */
                $bucket = new EmptyResultBucket();
                $line = yield $bucket;
                continue;
            }

            $line = yield new AcceptanceResultBucket($line);
        }
    }
}
