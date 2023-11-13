<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceAppendableResultBucket;
use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\AppendableIteratorAcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template Type
 *
 * @implements TransformerInterface<Type, list<Type>>
 * @implements FlushableInterface<non-empty-array<int, Type>>
 */
class BatchingTransformer implements TransformerInterface, FlushableInterface
{
    /** @var list<Type> */
    private array $batch = [];

    public function __construct(
        private readonly int $batchSize
    ) {
    }

    /** @return \Generator<array-key, AcceptanceResultBucket<non-empty-array<int, Type>>|EmptyResultBucket, Type|null, void> */
    public function transform(): \Generator
    {
        $this->batch = [];
        $itemCount = 0;

        $line = yield new EmptyResultBucket();
        /** @phpstan-ignore-next-line */
        while (true) {
            if ($line === null) {
                $line = yield new EmptyResultBucket();
                continue;
            }
            $this->batch[] = $line;

            if ($this->batchSize <= ++$itemCount) {
                $line = yield new AcceptanceResultBucket($this->batch);
                $itemCount = 0;
                $this->batch = [];
            } else {
                $line = yield new EmptyResultBucket();
            }
        }
    }

    public function flush(): ResultBucketInterface
    {
        if (count($this->batch) <= 0) {
            return new EmptyResultBucket();
        }
        return new AcceptanceResultBucket($this->batch);
    }
}
