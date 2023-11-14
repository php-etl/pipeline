<?php

declare(strict_types=1);

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Pipeline\PipelineRunner;
use Kiboko\Component\Pipeline\StepCode;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\NullRejection;
use Kiboko\Contract\Pipeline\NullState;
use Kiboko\Contract\Pipeline\NullStepRejection;
use Kiboko\Contract\Pipeline\NullStepState;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class PipelineTest extends IterableTestCase
{
    public function testExtractorWithoutFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()), new NullState());

        $pipeline->extract(
            StepCode::fromString('extractor'),
            new class() implements ExtractorInterface {
                public function extract(): iterable
                {
                    yield new AcceptanceResultBucket('lorem');
                    yield new AcceptanceResultBucket('ipsum');
                    yield new AcceptanceResultBucket('dolor');
                }
            },
            new NullStepRejection(),
            new NullStepState()
        );

        $this->assertIteration(
            new \ArrayIterator(['lorem', 'ipsum', 'dolor']),
            $pipeline->walk()
        );
    }

    public function testTransformerWithoutFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()), new NullState());

        $pipeline->feed(['lorem'], ['ipsum'], ['dolor']);

        $pipeline->transform(
            StepCode::fromString('transformer'),
            new class() implements TransformerInterface {
                public function transform(): \Generator
                {
                    $line = yield;
                    $line = yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                    $line = yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                    yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                }
            },
            new NullStepRejection(),
            new NullStepState()
        );

        $this->assertIteration(
            new \ArrayIterator([['yberz'], ['vcfhz'], ['qbybe']]),
            $pipeline->walk()
        );
    }

    public function testTransformerWithFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()), new NullState());

        $pipeline->feed(['lorem'], ['ipsum'], ['dolor']);

        $pipeline->transform(
            StepCode::fromString('transformer'),
            new class() implements TransformerInterface, FlushableInterface {
                public function transform(): \Generator
                {
                    $line = yield;
                    $line = yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                    $line = yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                    yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                }

                public function flush(): ResultBucketInterface
                {
                    return new AcceptanceResultBucket([str_rot13('sit amet')]);
                }
            },
            new NullStepRejection(),
            new NullStepState()
        );

        $this->assertIteration(
            new \ArrayIterator([['yberz'], ['vcfhz'], ['qbybe'], ['fvg nzrg']]),
            $pipeline->walk()
        );
    }

    public function testLoaderWithoutFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()), new NullState());

        $pipeline->feed(['lorem'], ['ipsum'], ['dolor']);

        $pipeline->load(
            StepCode::fromString('loader'),
            new class() implements LoaderInterface {
                public function load(): \Generator
                {
                    $line = yield;
                    $line = yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                    $line = yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                    yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                }
            },
            new NullStepRejection(),
            new NullStepState()
        );

        $this->assertIteration(
            new \ArrayIterator([['yberz'], ['vcfhz'], ['qbybe']]),
            $pipeline->walk()
        );
    }

    public function testLoaderWithFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()), new NullState());

        $pipeline->feed(['lorem'], ['ipsum'], ['dolor']);

        $pipeline->load(
            StepCode::fromString('loader'),
            new class() implements LoaderInterface, FlushableInterface {
                public function load(): \Generator
                {
                    $line = yield;
                    $line = yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                    $line = yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                    yield new AcceptanceResultBucket(array_map(fn (string $item) => str_rot13($item), $line));
                }

                public function flush(): ResultBucketInterface
                {
                    return new AcceptanceResultBucket([str_rot13('sit amet')]);
                }
            },
            new NullStepRejection(),
            new NullStepState()
        );

        $this->assertIteration(
            new \ArrayIterator([['yberz'], ['vcfhz'], ['qbybe'], ['fvg nzrg']]),
            $pipeline->walk()
        );
    }
}
