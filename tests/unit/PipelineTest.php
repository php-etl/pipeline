<?php

declare(strict_types=1);

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Pipeline\PipelineRunner;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\NullRejection;
use Kiboko\Contract\Pipeline\NullState;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class PipelineTest extends IterableTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function extractorWithoutFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()));

        $pipeline->extract(new class() implements ExtractorInterface {
            public function extract(): iterable
            {
                yield new AcceptanceResultBucket('lorem');
                yield new AcceptanceResultBucket('ipsum');
                yield new AcceptanceResultBucket('dolor');
            }
        }, new NullRejection(), new NullState());

        $this->assertIteration(
            new \ArrayIterator(['lorem', 'ipsum', 'dolor']),
            $pipeline->walk()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function transformerWithoutFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()));

        $pipeline->feed(['lorem'], ['ipsum'], ['dolor']);

        $pipeline->transform(new class() implements TransformerInterface {
            public function transform(): \Generator
            {
                $line = yield;
                $line = yield new AcceptanceResultBucket(str_rot13((string) $line));
                $line = yield new AcceptanceResultBucket(str_rot13((string) $line));
                yield new AcceptanceResultBucket(str_rot13((string) $line));
            }
        }, new NullRejection(), new NullState());

        $this->assertIteration(
            new \ArrayIterator(['yberz', 'vcfhz', 'qbybe']),
            $pipeline->walk()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function transformerWithFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()));

        $pipeline->feed(['lorem'], ['ipsum'], ['dolor']);

        $pipeline->transform(new class() implements TransformerInterface, FlushableInterface {
            public function transform(): \Generator
            {
                $line = yield;
                $line = yield new AcceptanceResultBucket(str_rot13((string) $line));
                $line = yield new AcceptanceResultBucket(str_rot13((string) $line));
                yield new AcceptanceResultBucket(str_rot13((string) $line));
            }

            public function flush(): ResultBucketInterface
            {
                return new AcceptanceResultBucket(str_rot13('sit amet'));
            }
        }, new NullRejection(), new NullState());

        $this->assertIteration(
            new \ArrayIterator(['yberz', 'vcfhz', 'qbybe', 'fvg nzrg']),
            $pipeline->walk()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function loaderWithoutFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()));

        $pipeline->feed(['lorem'], ['ipsum'], ['dolor']);

        $pipeline->load(new class() implements LoaderInterface {
            public function load(): \Generator
            {
                $line = yield;
                $line = yield new AcceptanceResultBucket(str_rot13((string) $line));
                $line = yield new AcceptanceResultBucket(str_rot13((string) $line));
                yield new AcceptanceResultBucket(str_rot13((string) $line));
            }
        }, new NullRejection(), new NullState());

        $this->assertIteration(
            new \ArrayIterator(['yberz', 'vcfhz', 'qbybe']),
            $pipeline->walk()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function loaderWithFlush(): void
    {
        $pipeline = new Pipeline(new PipelineRunner(new NullLogger()));

        $pipeline->feed(['lorem'], ['ipsum'], ['dolor']);

        $pipeline->load(new class() implements LoaderInterface, FlushableInterface {
            public function load(): \Generator
            {
                $line = yield;
                $line = yield new AcceptanceResultBucket(str_rot13((string) $line));
                $line = yield new AcceptanceResultBucket(str_rot13((string) $line));
                yield new AcceptanceResultBucket(str_rot13((string) $line));
            }

            public function flush(): ResultBucketInterface
            {
                return new AcceptanceResultBucket(str_rot13('sit amet'));
            }
        }, new NullRejection(), new NullState());

        $this->assertIteration(
            new \ArrayIterator(['yberz', 'vcfhz', 'qbybe', 'fvg nzrg']),
            $pipeline->walk()
        );
    }
}
