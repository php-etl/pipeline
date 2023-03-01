<?php

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Component\PHPUnitExtension\Assert\PipelineAssertTrait;
use Kiboko\Component\Pipeline\PipelineRunner;
use Kiboko\Contract\Pipeline\NullRejection;
use Kiboko\Contract\Pipeline\NullState;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class PipelineRunnerTest extends TestCase
{
    use PipelineAssertTrait;

    public function providerRun()
    {
        return;
        // Test if pipeline can walk items, without adding or removing any item
        yield [
            'source' => new \ArrayIterator([
                'lorem',
                'ipsum',
                'dolor',
                'sit',
                'amet',
                'consecutir',
            ]),
            'callback' => function() {
                $item = yield;
                while (true) {
                    $item = yield new AcceptanceResultBucket(strrev($item));
                }
            },
            'expected' => [
                'merol',
                'muspi',
                'rolod',
                'tis',
                'tema',
                'ritucesnoc',
            ]
        ];

        // Test if pipeline can walk items, while removing some items
        yield [
            'source' => new \ArrayIterator([
                'lorem',
                'ipsum',
                'dolor',
                'sit',
                'amet',
                'consecutir',
            ]),
            'callback' => function() {
                $item = yield;
                while (true) {
                    static $i = 0;
                    if ($i++ % 2 === 0) {
                        $item = yield new AcceptanceResultBucket(strrev($item));
                    } else {
                        $item = yield new EmptyResultBucket();
                    }
                }
            },
            'expected' => [
                'merol',
                'rolod',
                'tema',
            ]
        ];

        // Test if pipeline can walk items, while adding some items
        yield [
            'source' => new \ArrayIterator([
                'lorem',
                'ipsum',
                'dolor',
                'sit',
                'amet',
                'consecutir',
            ]),
            'callback' => function() {
                $item = yield;
                while (true) {
                    $item = yield new AcceptanceResultBucket(
                        $item,
                        strrev($item)
                    );
                }
            },
            'expected' => [
                'lorem',
                'merol',
                'ipsum',
                'muspi',
                'dolor',
                'rolod',
                'sit',
                'tis',
                'amet',
                'tema',
                'consecutir',
                'ritucesnoc',
            ]
        ];
    }

    /**
     * @param \Iterator $source
     * @param callable  $callback
     * @param array     $expected
     *
     * @dataProvider providerRun
     */
    public function testRun(\Iterator $source, callable $callback, array $expected)
    {
        $this->markTestSkipped();
        $run = new PipelineRunner(new NullLogger());

        $it = $run->run($source, $callback(), new NullRejection(), new NullState());

        $this->assertDoesIterateExactly(new \ArrayIterator($expected), $it);
    }
}
