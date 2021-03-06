<?php

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AcceptanceAppendableResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

class BatchingTransformer implements TransformerInterface, FlushableInterface
{
    private ResultBucketInterface $bucket;

    public function __construct(private int $batchSize)
    {
        $this->bucket = new EmptyResultBucket();
    }

    public function transform(): \Generator
    {
        $this->bucket = new AcceptanceAppendableResultBucket();
        $itemCount = 0;

        $line = yield;
        while (true) {
            $this->bucket->append($line);

            if ($this->batchSize <= ++$itemCount) {
                $line = yield $this->bucket;
                $itemCount = 0;
                $this->bucket = new AcceptanceAppendableResultBucket();
            } else {
                $line = yield new EmptyResultBucket();
            }
        }
    }

    public function flush(): ResultBucketInterface
    {
        return $this->bucket;
    }
}
