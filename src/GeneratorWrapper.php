<?php

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Bucket\ResultBucketInterface;

final class GeneratorWrapper
{
    public function __construct(
        private \Generator $async,
    ) {}

    public function rewind(\Iterator $source): void
    {
        $source->rewind();
    }

    public function next(\Iterator $source): void
    {
        $source->next();
        $this->async->next();
    }

    public function valid(\Iterator $source): bool
    {
        return $source->valid();
    }

    public function send(mixed $value): ?ResultBucketInterface
    {
        return $this->async->send($value);
    }
}
