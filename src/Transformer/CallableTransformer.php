<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template InputType of non-empty-array<array-key, mixed>|object
 * @template OutputType of non-empty-array<array-key, mixed>|object
 *
 * @template-implements TransformerInterface<InputType, OutputType>
 */
class CallableTransformer implements TransformerInterface
{
    /** @var callable(InputType|null $item): OutputType */
    private $callback;

    /**
     * @param callable(InputType $item): OutputType $callback
     */
    public function __construct(
        callable $callback,
    ) {
        $this->callback = $callback;
    }

    /**
     * @return \Generator<int<0, max>, AcceptanceResultBucket<OutputType>|EmptyResultBucket, InputType|null, void>
     */
    public function transform(): \Generator
    {
        $callback = $this->callback;

        $line = yield new EmptyResultBucket();
        /** @phpstan-ignore-next-line */
        while (true) {
            if ($line === null) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            $line = yield new AcceptanceResultBucket($callback($line));
        }
    }
}
