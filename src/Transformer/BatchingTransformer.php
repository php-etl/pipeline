<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template InputType of non-empty-array<array-key, mixed>|object
 * @template OutputType of non-empty-array<array-key, InputType>
 *
 * @implements TransformerInterface<InputType, OutputType>
 * @implements FlushableInterface<OutputType>
 */
class BatchingTransformer implements TransformerInterface, FlushableInterface
{
    /** @var list<InputType> */
    private array $batch = [];

    /**
     * @param positive-int $batchSize
     */
    public function __construct(
        private readonly int $batchSize
    ) {
    }

    /** @return \Generator<int<0, max>, ResultBucketInterface<OutputType>|EmptyResultBucket, InputType|null, void> */
    public function transform(): \Generator
    {
        $this->batch = [];

        $line = yield new EmptyResultBucket();
        /** @phpstan-ignore-next-line */
        while (true) {
            if ($line === null) {
                $line = yield new EmptyResultBucket();
                continue;
            }
            $this->batch[] = $line;

            if (count($this->batch) >= $this->batchSize) {
                /** @phpstan-ignore-next-line */
                $line = yield new AcceptanceResultBucket($this->batch);
                $this->batch = [];
                continue;
            }

            $line = yield new EmptyResultBucket();
        }
    }

    /** @return AcceptanceResultBucket<OutputType>|EmptyResultBucket */
    public function flush(): ResultBucketInterface
    {
        if (count($this->batch) <= 0) {
            return new EmptyResultBucket();
        }
        /** @phpstan-ignore-next-line */
        return new AcceptanceResultBucket($this->batch);
    }
}
