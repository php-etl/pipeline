<?php

namespace Kiboko\Component\Pipeline;

use Kiboko\Component\Bucket\AcceptanceAppendableResultBucket;
use Kiboko\Contract\Pipeline\ExtractingInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\ForkingInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\LoadingInterface;
use Kiboko\Contract\Pipeline\PipelineInterface;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RunnableInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Kiboko\Contract\Pipeline\TransformingInterface;
use Kiboko\Contract\Pipeline\WalkableInterface;

class Pipeline implements PipelineInterface, ForkingInterface, WalkableInterface, RunnableInterface
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

    public function fork(callable ...$builders): ForkingInterface
    {
        error_log('The Pipeline::fork() method is not fully functional and has been deprecated for now. No replacement exists.', E_USER_DEPRECATED);
        $runner = $this->runner;
        $handlers = [];
        foreach ($builders as $builder) {
            $handlers[] = $handler = new class(new Pipeline($runner)) implements \IteratorAggregate {
                /** @var \Iterator */
                public $consumer;

                public function __construct(public PipelineInterface $pipeline)
                {
                    $this->consumer = $pipeline->walk();
                    $this->consumer->rewind();
                }

                public function getIterator()
                {
                    return $this->consumer;
                }
            };

            $builder($handler->pipeline);
        }

        $this->subject = $this->runner->run($this->subject, (function (array $handlers) {
            $line = yield;

            while (true) {
                $bucket = new AcceptanceAppendableResultBucket();

                /** @var \Iterator $handler */
                foreach ($handlers as $handler) {
                    $handler->pipeline->feed($line);
                    $bucket->append(new \NoRewindIterator($handler));
                }

                $line = yield $bucket;
            }
        })($handlers));

        return $this;
    }

    /**
     * @param ExtractorInterface $extractor
     *
     * @return $this
     */
    public function extract(ExtractorInterface $extractor): ExtractingInterface
    {
        $extract = $extractor->extract();
        if (is_array($extract)) {
            $this->source->append(new \ArrayIterator($extract));
        } elseif ($extract instanceof \Iterator) {
            $this->source->append($extract);
        } elseif ($extract instanceof \Traversable) {
            $this->source->append(new \IteratorIterator($extract));
        } else {
            throw new \RuntimeException('Invalid data source, expecting array or Traversable.');
        }

        return $this;
    }

    /**
     * @param TransformerInterface $transformer
     *
     * @return $this
     */
    public function transform(TransformerInterface $transformer): TransformingInterface
    {
        if ($transformer instanceof FlushableInterface) {
            $iterator = new \AppendIterator();

            $iterator->append(
                $this->runner->run($this->subject, $transformer->transform())
            );
            $iterator->append(
                $this->runner->run(
                    new \ArrayIterator([null]),
                    (function () use ($transformer): \Generator {
                        yield;
                        yield $transformer->flush();
                    })()
                )
            );
        } else {
            $iterator = $this->runner->run($this->subject, $transformer->transform());
        }

        $this->subject = new \NoRewindIterator($iterator);

        return $this;
    }

    /**
     * @param LoaderInterface $loader
     *
     * @return $this
     */
    public function load(LoaderInterface $loader): LoadingInterface
    {
        if ($loader instanceof FlushableInterface) {
            $iterator = new \AppendIterator();

            $iterator->append(
                $this->runner->run($this->subject, $loader->load())
            );

            $iterator->append(
                $this->runner->run(
                    new \ArrayIterator([null]),
                    (function () use ($loader): \Generator {
                        yield;
                        yield $loader->flush();
                    })()
                )
            );
        } else {
            $iterator = $this->runner->run($this->subject, $loader->load());
        }

        $this->subject = new \NoRewindIterator($iterator);

        return $this;
    }

    /**
     * @return \Iterator
     */
    public function walk(): \Iterator
    {
        yield from $this->subject;
    }

    /**
     * @return int
     */
    public function run(): int
    {
        return iterator_count($this->walk());
    }
}
