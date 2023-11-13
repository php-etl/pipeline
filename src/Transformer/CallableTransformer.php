<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template InputType
 * @template OutputType
 *
 * @template-implements TransformerInterface<InputType, OutputType>
 */
class CallableTransformer implements TransformerInterface
{
    /** @var callable(InputType|null $item): OutputType */
    private $callback;

    /**
     * @param callable(InputType|null $item): OutputType $callback
     */
    public function __construct(
        callable $callback,
    ) {
        $this->callback = $callback;
    }

    /**
     * @return \Generator<array-key, AcceptanceResultBucket<OutputType>|EmptyResultBucket, InputType|null, void>
     */
    public function transform(): \Generator
    {
        $callback = $this->callback;

        $line = yield new EmptyResultBucket();
        /** @phpstan-ignore-next-line */
        while (true) {
            $line = yield new AcceptanceResultBucket($callback($line));
        }
    }
}
