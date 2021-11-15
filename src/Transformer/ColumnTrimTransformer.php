<?php

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

/**
 * @template Type
 * @template-implements TransformerInterface<array>
 */
class ColumnTrimTransformer implements TransformerInterface
{
    /** @param list<string> $columnsToTrim */
    public function __construct(
        private array $columnsToTrim
    ) {
    }

    public function transform(): \FIber
    {
        $line = \Fiber::suspend(new EmptyResultBucket());
        while (true) {
            if ($line === null) {
                $line = \Fiber::suspend(new EmptyResultBucket());
                continue;
            }
            foreach ($this->columnsToTrim as $column) {
                if (!isset($line[$column])) {
                    continue;
                }

                $line[$column] = trim($line[$column]);
            }
            $line = \Fiber::suspend(new AcceptanceResultBucket($line));
        }
    }
}
