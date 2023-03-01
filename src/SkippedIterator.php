<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline;

final class SkippedIterator implements \Iterator
{
    private \Iterator $inner;

    public function __construct(
        \Iterator $inner,
        private int $offset = 0,
    ) {
        $this->inner = $inner;
    }

    public function current(): mixed
    {
        return $this->inner->current();
    }

    public function next(): void
    {
        $this->inner->next();
    }

    public function key(): mixed
    {
        return $this->inner->key();
    }

    public function valid(): bool
    {
        return $this->inner->valid();
    }

    public function rewind(): void
    {
        $this->inner->rewind();
        $count = 0;
        while ($count++ < $this->offset && $this->inner->valid()) {
            $this->inner->next();
        }
    }
}
