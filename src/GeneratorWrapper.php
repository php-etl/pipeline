<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Bucket\ResultBucketInterface;

/**
 * @template Type
 */
class GeneratorWrapper
{
    /** @param \Iterator<array-key, Type> ...$iterators */
    public function rewind(\Iterator ...$iterators): void
    {
        foreach ($iterators as $iterator) {
            $iterator->rewind();
        }
    }

    /** @param \Iterator<array-key, Type> ...$iterators */
    public function next(\Iterator ...$iterators): void
    {
        foreach ($iterators as $iterator) {
            $iterator->next();
        }
    }

    /** @param \Iterator<array-key, Type> ...$iterators */
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
     * @param \Generator<array-key, ResultBucketInterface<Type>, Type, void> ...$generators
     */
    public function send($value, \Generator ...$generators): \Generator
    {
        foreach ($generators as $generator) {
            yield $generator->send($value);
        }
    }
}
