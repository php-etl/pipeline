<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline;

final class PaddedIterator implements \Iterator
{
    private \Iterator $inner;

    public function __construct(
        \Iterator $inner,
        private int $offset = 1,
    ) {
        if ($this->offset <= 0) {
            $this->inner = $inner;
            return;
        }

        $this->inner = new \AppendIterator();
        $this->inner->append(new \ArrayIterator(array_pad([], $this->offset, null)));
        $this->inner->append($inner);
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
    }
}
