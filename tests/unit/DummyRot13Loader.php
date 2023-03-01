<?php

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;

final class DummyRot13Loader implements LoaderInterface
{
    public function load(): \Generator
    {
        $line = yield;
        while (true) {
            $line = yield new AcceptanceResultBucket(str_rot13($line));
        }
    }
}
