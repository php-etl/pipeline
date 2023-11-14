<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Bucket\ResultBucketInterface;

/**
 * @template Type of non-empty-array<array-key, mixed>|object
 */
class GeneratorWrapper
{
    /** @param \Iterator<int<0, max>, Type> ...$iterators */
    public function rewind(\Iterator ...$iterators): void
    {
        foreach ($iterators as $iterator) {
            $iterator->rewind();
        }
    }

    /** @param \Iterator<int<0, max>, Type> ...$iterators */
    public function next(\Iterator ...$iterators): void
    {
        foreach ($iterators as $iterator) {
            $iterator->next();
        }
    }

    /** @param \Iterator<int<0, max>, Type> ...$iterators */
    public function valid(\Iterator ...$iterators): bool
    {
        foreach ($iterators as $iterator) {
            if (!$iterator->valid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Type $value
     * @param \Generator<int<0, max>, ResultBucketInterface<Type>, Type, void> ...$generators
     */
    public function send($value, \Generator ...$generators): \Generator
    {
        foreach ($generators as $generator) {
            yield $generator->send($value);
        }
    }
}
