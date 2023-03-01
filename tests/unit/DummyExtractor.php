<?php

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;

final class DummyExtractor implements ExtractorInterface
{
    public function extract(): iterable
    {
        yield new AcceptanceResultBucket('lorem');
        yield new AcceptanceResultBucket('ipsum');
        yield new AcceptanceResultBucket('dolor');
    }
}
