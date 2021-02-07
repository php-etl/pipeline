<?php

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

class ColumnTrimTransformer implements TransformerInterface
{
    /**
     * @var array
     */
    private $columnsToTrim;

    /**
     * @param array $columnsToTrim
     */
    public function __construct(array $columnsToTrim)
    {
        $this->columnsToTrim = $columnsToTrim;
    }

    public function transform(): \Generator
    {
        $line = yield;
        do {
            foreach ($this->columnsToTrim as $column) {
                if (!isset($line[$column])) {
                    continue;
                }

                $line[$column] = trim($line[$column]);
            }
        } while ($line = yield new AcceptanceResultBucket($line));
    }
}
