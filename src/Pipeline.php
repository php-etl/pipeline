<?php

declare(strict_types=1);

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
    private readonly \AppendIterator $source;
    private iterable $subject;

    public function __construct(private readonly PipelineRunnerInterface $runner, ?\Iterator $source = null)
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
        while ($line = yield $line) {
        }
    }

    public function extract(
        ExtractorInterface $extractor,
        RejectionInterface $rejection,
        StateInterface $state,
    ): ExtractingInterface {
        $extract = $extractor->extract();
        if (\is_array($extract)) {
            $this->source->append(
                $this->runner->run(
                    new \ArrayIterator($extract),
                    $this->passThroughCoroutine(),
                    $rejection,
                    $state
                )
            );
        } elseif ($extract instanceof \Iterator) {
            $this->source->append(
                $this->runner->run(
                    $extract,
                    $this->passThroughCoroutine(),
                    $rejection,
                    $state
                )
            );
        } elseif ($extract instanceof \Traversable) {
            $this->source->append(
                $this->runner->run(
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
                $this->runner->run(
                    $this->subject,
                    $transformer->transform(),
                    $rejection,
                    $state,
                )
            );
            $iterator->append(
                $this->runner->run(
                    new \ArrayIterator([null]),
                    (function () use ($transformer): \Generator {
                        yield;
                        yield $transformer->flush();
                    })(),
                    $rejection,
                    $state,
                )
            );
        } else {
            $iterator = $this->runner->run(
                $this->subject,
                $transformer->transform(),
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
                    $loader->load(),
                    $rejection,
                    $state,
                )
            );

            $iterator->append(
                $this->runner->run(
                    new \ArrayIterator([null]),
                    (function () use ($loader): \Generator {
                        yield;
                        yield $loader->flush();
                    })(),
                    $rejection,
                    $state,
                )
            );
        } else {
            $iterator = $this->runner->run(
                $this->subject,
                $loader->load(),
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

    public function run(int $interval = 1000): int
    {
        return iterator_count($this->walk());
    }
}
