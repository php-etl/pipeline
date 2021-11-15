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

final class Pipeline implements PipelineInterface, WalkableInterface, RunnableInterface
{
    private \AppendIterator $source;
    private iterable $subject;

    public function __construct(private PipelineRunnerInterface $runner, ?\Iterator $source = null)
    {
        $this->source = new \AppendIterator();
        $this->source->append($source ?? new \EmptyIterator());

        $this->subject = new \NoRewindIterator($this->source);
    }

    public function feed(array|object ...$data): void
    {
        $this->source->append(new \ArrayIterator($data));
    }

    private function passThroughCoroutine(): \Generator
    {
        $line = yield;
        while ($line = yield $line);
    }

    public function extract(
        ExtractorInterface $extractor,
        RejectionInterface $rejection,
        StateInterface $state,
    ): ExtractingInterface {
        $this->source->append(
            $this->runner->run(
                new \InfiniteIterator(new \ArrayIterator([null])),
                new \Fiber(function() use($extractor) {
                    $extractor->extract();
                }),
                $rejection,
                $state,
            )
        );

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
                $this->runner->run(
                    $this->subject,
                    new \Fiber(\Closure::fromCallable([$transformer, 'transform'])),
                    $rejection,
                    $state,
                )
            );

            $iterator->append(
                $this->runner->run(
                    new \InfiniteIterator(new \ArrayIterator([null])),
                    new \Fiber(function() use ($transformer) {
                        \Fiber::suspend(null);
                        \Fiber::suspend($transformer->flush());
                    }),
                    $rejection,
                    $state,
                )
            );
        } else {
            $iterator = $this->runner->run(
                $this->subject,
                new \Fiber(\Closure::fromCallable([$transformer, 'transform'])),
                $rejection,
                $state,
            );
        }

        $this->subject = new \NoRewindIterator($iterator);

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
                $this->runner->run(
                    $this->subject,
                    new \Fiber(\Closure::fromCallable([$loader, 'load'])),
                    $rejection,
                    $state,
                )
            );

            $iterator->append(
                $this->runner->run(
                    new \InfiniteIterator(new \ArrayIterator([null])),
                    new \Fiber(function() use ($loader) {
                        \Fiber::suspend(null);
                        \Fiber::suspend($loader->flush());
                    }),
                    $rejection,
                    $state,
                )
            );
        } else {
            $iterator = $this->runner->run(
                $this->subject,
                new \Fiber(\Closure::fromCallable([$loader, 'load'])),
                $rejection,
                $state,
            );
        }

        $this->subject = new \NoRewindIterator($iterator);

        return $this;
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
