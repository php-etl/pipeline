<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template InputType
 * @template OutputType of list<InputType>
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
    ) {}

    /** @return \Generator<int, ResultBucketInterface<OutputType>, InputType|null, void> */
    public function transform(): \Generator
    {
        $this->batch = [];

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
            $this->batch[] = $line;

            if (\count($this->batch) >= $this->batchSize) {
                /** @var EmptyResultBucket<OutputType> $bucket */
                $bucket = new AcceptanceResultBucket($this->batch);
                $line = yield $bucket;

                $this->batch = [];
                continue;
            }

            /** @var EmptyResultBucket<OutputType> $bucket */
            $bucket = new EmptyResultBucket();
            $line = yield $bucket;
        }
    }

    /** @return ResultBucketInterface<OutputType> */
    public function flush(): ResultBucketInterface
    {
        if (\count($this->batch) <= 0) {
            return new EmptyResultBucket();
        }

        /* @phpstan-ignore-next-line */
        return new AcceptanceResultBucket($this->batch);
    }
}
