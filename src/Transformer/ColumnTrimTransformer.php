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
 * @implements TransformerInterface<InputType, OutputType>
 */
class ColumnTrimTransformer implements TransformerInterface
{
    /** @param list<string> $columnsToTrim */
    public function __construct(
        private readonly array $columnsToTrim
    ) {
    }

    /** @return \Generator<int<0, max>, AcceptanceResultBucket<OutputType>|EmptyResultBucket, InputType|null, void> */
    public function transform(): \Generator
    {
        $line = yield new EmptyResultBucket();
        /** @phpstan-ignore-next-line */
        while (true) {
            if (null === $line) {
                $line = yield new EmptyResultBucket();
                continue;
            }
            foreach ($this->columnsToTrim as $column) {
                if (!isset($line[$column])) {
                    continue;
                }

                $line[$column] = trim((string) $line[$column]);
            }
            /** @phpstan-ignore-next-line */
            $line = yield new AcceptanceResultBucket($line);
        }
    }
}
