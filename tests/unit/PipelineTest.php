<?php declare(strict_types=1);

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\PHPUnitExtension\Assert\PipelineAssertTrait;
use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Pipeline\PipelineRunner;
use Kiboko\Contract\Pipeline\NullRejection;
use Kiboko\Contract\Pipeline\NullState;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    use PipelineAssertTrait;

    public function testExtractorWithoutFlush()
    {
        $pipeline = new Pipeline(new PipelineRunner(null));

        $pipeline->extract(new DummyExtractor(), new NullRejection(), new NullState());

        $this->assertDoesIterateExactly(
            new \ArrayIterator(['lorem', 'ipsum', 'dolor']),
            $pipeline->walk()
        );
    }

    public function testTransformerWithoutFlush()
    {
        $pipeline = new Pipeline(new PipelineRunner(null));

        $pipeline->feed('lorem', 'ipsum', 'dolor');
//        $pipeline->feed([new AcceptanceResultBucket('lorem', 'ipsum', 'dolor')], new NullRejection(), new NullState());

        $pipeline->transform(new DummyRot13Transformer(), new NullRejection(), new NullState());

        $this->assertDoesIterateExactly(
            new \ArrayIterator(['yberz', 'vcfhz', 'qbybe']),
            $pipeline->walk()
        );
    }

    public function testTransformerWithFlush()
    {
        $pipeline = new Pipeline(new PipelineRunner(null));

        $pipeline->feed('lorem', 'ipsum', 'dolor');
//        $pipeline->feed([new AcceptanceResultBucket('lorem', 'ipsum', 'dolor')], new NullRejection(), new NullState());

        $pipeline->transform(new DummyRot13FlushableTransformer(), new NullRejection(), new NullState());

        $this->assertDoesIterateExactly(
            new \ArrayIterator(['yberz', 'vcfhz', 'qbybe', 'fvg nzrg']),
            $pipeline->walk()
        );
    }

    public function testLoaderWithoutFlush()
    {
        $pipeline = new Pipeline(new PipelineRunner(null));

        $pipeline->feed('lorem', 'ipsum', 'dolor');
//        $pipeline->feed([new AcceptanceResultBucket('lorem', 'ipsum', 'dolor')], new NullRejection(), new NullState());

        $pipeline->load(new DummyRot13Loader(), new NullRejection(), new NullState());

        $this->assertDoesIterateExactly(
            new \ArrayIterator(['yberz', 'vcfhz', 'qbybe']),
            $pipeline->walk()
        );
    }

    public function testLoaderWithFlush()
    {
        $pipeline = new Pipeline(new PipelineRunner(null));

        $pipeline->feed('lorem', 'ipsum', 'dolor');
//        $pipeline->feed([new AcceptanceResultBucket('lorem', 'ipsum', 'dolor')], new NullRejection(), new NullState());

        $pipeline->load(new DummyRot13FlushableLoader(), new NullRejection(), new NullState());

        $this->assertDoesIterateExactly(
            new \ArrayIterator(['yberz', 'vcfhz', 'qbybe', 'fvg nzrg']),
            $pipeline->walk()
        );
    }
}
