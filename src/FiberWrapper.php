<?php

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Bucket\ResultBucketInterface;

final class FiberWrapper
{
    public function __construct(
        private \Fiber $async,
    ) {}

    public function rewind(\Iterator $source): void
    {
        $source->rewind();
        $this->async->start();
    }

    public function next(\Iterator $source): void
    {
        $source->next();
    }

    public function valid(\Iterator $source): bool
    {
        return $source->valid();
    }

    public function send(mixed $value): ?ResultBucketInterface
    {
        return $this->async->resume($value);
    }
}
