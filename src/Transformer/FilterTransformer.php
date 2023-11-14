<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template Type of non-empty-array<array-key, mixed>|object
 *
 * @implements TransformerInterface<Type, Type>
 */
class FilterTransformer implements TransformerInterface
{
    /** @var callable(Type $item): bool */
    private $callback;

    /** @param callable(Type $item): bool $callback */
    public function __construct(
        callable $callback,
    ) {
        $this->callback = $callback;
    }

    /**
     * @return \Generator<positive-int, AcceptanceResultBucket<Type>|EmptyResultBucket, Type|null, void>
     */
    public function transform(): \Generator
    {
        $callback = $this->callback;

        $line = yield new EmptyResultBucket();
        /** @phpstan-ignore-next-line */
        while (true) {
            if (null === $line || !$callback($line)) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            $line = yield new AcceptanceResultBucket($line);
        }
    }
}
