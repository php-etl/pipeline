<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template InputType of array<array-key, string>
 * @template OutputType of array<array-key, string>
 *
 * @implements TransformerInterface<InputType, OutputType>
 */
class ColumnTrimTransformer implements TransformerInterface
{
    /** @param list<string> $columnsToTrim */
    public function __construct(
        private readonly array $columnsToTrim
    ) {}

    /** @return \Generator<int, ResultBucketInterface<OutputType>, InputType|null, void> */
    public function transform(): \Generator
    {
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
            foreach ($this->columnsToTrim as $column) {
                if (!isset($line[$column])) {
                    continue;
                }

                $line[$column] = trim((string) $line[$column]);
            }

            /** @var AcceptanceResultBucket<OutputType> $bucket */
            $bucket = new AcceptanceResultBucket($line);
            $line = yield $bucket;
        }
    }
}
