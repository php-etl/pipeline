<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template InputType
 * @template OutputType
 *
 * @template-implements TransformerInterface<InputType, OutputType>
 */
class CallableTransformer implements TransformerInterface
{
    /** @var callable(InputType|null): OutputType */
    private $callback;

    /**
     * @param callable(InputType|null $item): OutputType $callback
     */
    public function __construct(
        callable $callback,
    ) {
        $this->callback = $callback;
    }

    /** @return \Generator<int, ResultBucketInterface<OutputType>, InputType|null, void> */
    public function transform(): \Generator
    {
        $callback = $this->callback;

        /** @var EmptyResultBucket<OutputType> $bucket */
        $bucket = new EmptyResultBucket();
        $line = yield $bucket;
        /* @phpstan-ignore-next-line */
        while (true) {
            if (null === $line) {
                /** @var EmptyResultBucket<OutputType> $bucket */
                $bucket = new EmptyResultBucket();
                $line = yield $bucket;
                continue;
            }

            /** @var AcceptanceResultBucket<OutputType> $bucket */
            $bucket = new AcceptanceResultBucket($callback($line));
            $line = yield $bucket;
        }
    }
}
