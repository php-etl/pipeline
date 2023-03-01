<?php

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

final class DummyRot13Transformer implements TransformerInterface
{
    public function transform(): \Generator
    {
        $line = yield;
        while (true) {
            $line = yield new AcceptanceResultBucket(str_rot13($line));
        }
    }
}
