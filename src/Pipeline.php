<?php

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Pipeline\ExtractingInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\LoadingInterface;
use Kiboko\Contract\Pipeline\PipelineInterface;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RejectionInterface;
use Kiboko\Contract\Pipeline\RunnableInterface;
use Kiboko\Contract\Pipeline\StateInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Kiboko\Contract\Pipeline\TransformingInterface;
use Kiboko\Contract\Pipeline\WalkableInterface;

class Pipeline implements PipelineInterface, WalkableInterface, RunnableInterface
{
    private \AppendIterator $source;
    private iterable $subject;

    public function __construct(private PipelineRunnerInterface $runner, ?\Iterator $source = null)
    {
        $this->source = new \AppendIterator();
        $this->source->append($source ?? new \EmptyIterator());

        $this->subject = new \NoRewindIterator($this->source);
    }

    public function feed(...$data): void
    {
        $this->source->append(new \ArrayIterator($data));
    }

    private function passThroughCoroutine(): \Generator
    {
        $line = yield;
        while ($line = yield $line);
    }

    private function flushingCoroutine(FlushableInterface $flushable): \Generator
    {
        yield;
        yield $flushable->flush();
    }

    public function extract(
        ExtractorInterface $extractor,
        RejectionInterface $rejection,
        StateInterface $state,
    ): ExtractingInterface {
        $extract = $extractor->extract();
        if (is_array($extract)) {
            $this->source->append(
                $this->offsetRun(
                    new \ArrayIterator($extract),
                    $this->passThroughCoroutine(),
                    $rejection,
                    $state
                )
            );
        } elseif ($extract instanceof \Iterator) {
            $this->source->append(
                $this->offsetRun(
                    $extract,
                    $this->passThroughCoroutine(),
                    $rejection,
                    $state
                )
            );
        } elseif ($extract instanceof \Traversable) {
            $this->source->append(
                $this->offsetRun(
                    new \IteratorIterator($extract),
                    $this->passThroughCoroutine(),
                    $rejection,
                    $state
                )
            );
        } else {
            throw new \RuntimeException('Invalid data source, expecting array or Traversable.');
        }

        return $this;
    }

    public function transform(
        TransformerInterface $transformer,
        RejectionInterface $rejection,
        StateInterface $state,
    ): TransformingInterface {
        if ($transformer instanceof FlushableInterface) {
            $iterator = new \AppendIterator();

            $iterator->append(
                $this->offsetRun(
                    $this->subject,
                    $transformer->transform(),
                    $rejection,
                    $state,
                )
            );
            $iterator->append(
                $this->offsetRun(
                    new PaddedIterator(new \EmptyIterator()),
                    $this->flushingCoroutine($transformer),
                    $rejection,
                    $state,
                )
            );

            $iterator = new \NoRewindIterator($iterator);
        } else {
            $iterator = $this->offsetRun(
                $this->subject,
                $transformer->transform(),
                $rejection,
                $state,
            );
        }

        $this->subject = $iterator;

        return $this;
    }

    public function load(
        LoaderInterface $loader,
        RejectionInterface $rejection,
        StateInterface $state,
    ): LoadingInterface {
        if ($loader instanceof FlushableInterface) {
            $iterator = new \AppendIterator();

            $iterator->append(
                $this->offsetRun(
                    $this->subject,
                    $loader->load(),
                    $rejection,
                    $state,
                )
            );

            $iterator->append(
                $this->offsetRun(
                    new PaddedIterator(new \EmptyIterator()),
                    $this->flushingCoroutine($loader),
                    $rejection,
                    $state,
                )
            );

            $iterator = new \NoRewindIterator($iterator);
        } else {
            $iterator = $this->offsetRun(
                $this->subject,
                $loader->load(),
                $rejection,
                $state,
            );
        }

        $this->subject = $iterator;

        return $this;
    }

    private function offsetRun(
        \Iterator $source,
        \Generator $async,
        RejectionInterface $rejection,
        StateInterface $state,
        int $offset = 1
    ): \Iterator {
        return new SkippedIterator(new \NoRewindIterator($this->runner->run($source, $async, $rejection, $state)), $offset);
    }

    public function walk(): \Iterator
    {
        yield from $this->subject;
    }

    public function run(): int
    {
        return iterator_count($this->walk());
    }
}
