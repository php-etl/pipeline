<?php

namespace Kiboko\Component\Pipeline\Transformer;

use Kiboko\Component\Bucket\AppendableIteratorAcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

/** @template Type */
class BatchingTransformer implements TransformerInterface, FlushableInterface
{
    private ResultBucketInterface $bucket;

    public function __construct(private int $batchSize)
    {
        $this->bucket = new EmptyResultBucket();
    }

    public function transform(): \Fiber
    {
        $this->bucket = new AppendableIteratorAcceptanceResultBucket();
        $itemCount = 0;

        $line = \Fiber::suspend(new EmptyResultBucket());
        while (true) {
            $this->bucket->append($line);

            if ($this->batchSize <= ++$itemCount) {
                $line = \Fiber::suspend($this->bucket);
                $itemCount = 0;
                $this->bucket = new AppendableIteratorAcceptanceResultBucket();
            } else {
                $line = \Fiber::suspend(new EmptyResultBucket());
            }
        }
    }

    public function flush(): ResultBucketInterface
    {
        return $this->bucket;
    }
}
