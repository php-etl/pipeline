<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template-implements TransformerInterface<non-empty-array<array-key, string>, non-empty-array<array-key, string>>
 */
class ColumnTrimTransformer implements TransformerInterface
{
    /** @param list<string> $columnsToTrim */
    public function __construct(
        private readonly array $columnsToTrim
    ) {
    }

    /**
     * @return \Generator<array-key, AcceptanceResultBucket<non-empty-array<array-key, string>>|EmptyResultBucket, non-empty-array<array-key, string>|null, void>
     */
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
            $line = yield new AcceptanceResultBucket($line);
        }
    }
}
