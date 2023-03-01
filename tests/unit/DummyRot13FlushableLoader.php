<?php

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;

final class DummyRot13FlushableLoader implements LoaderInterface, FlushableInterface
{
    public function load(): \Generator
    {
        $line = yield;
        while (true) {
            $line = yield new AcceptanceResultBucket(str_rot13($line));
        }
    }

    public function flush(): ResultBucketInterface
    {
        return new AcceptanceResultBucket(str_rot13('sit amet'));
    }
}
