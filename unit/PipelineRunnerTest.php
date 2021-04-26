<?php

namespace unit\Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Component\Pipeline\PipelineRunner;
use Kiboko\Contract\Pipeline\NullRejection;
use Kiboko\Contract\Pipeline\NullState;
use Psr\Log\NullLogger;

class PipelineRunnerTest extends IterableTestCase
{
    public function providerRun()
    {
        // Test if pipeline can walk items, without adding or removing any item
        yield [
            new \ArrayIterator([
                'lorem',
                'ipsum',
                'dolor',
                'sit',
                'amet',
                'consecutir',
            ]),
            function() {
                $item = yield;
                while (true) {
                    $item = yield new AcceptanceResultBucket(strrev($item));
                }
            },
            [
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
            new \ArrayIterator([
                'lorem',
                'ipsum',
                'dolor',
                'sit',
                'amet',
                'consecutir',
            ]),
            function() {
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
            [
                'merol',
                'rolod',
                'tema',
            ]
        ];

        // Test if pipeline can walk items, while adding some items
        yield [
            new \ArrayIterator([
                'lorem',
                'ipsum',
                'dolor',
                'sit',
                'amet',
                'consecutir',
            ]),
            function() {
                $item = yield;
                while (true) {
                    $item = yield new AcceptanceResultBucket(
                        $item,
                        strrev($item)
                    );
                }
            },
            [
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
        $run = new PipelineRunner(new NullLogger());

        $it = $run->run($source, $callback(), new NullRejection(), new NullState());

        $this->assertIteration(new \ArrayIterator($expected), $it);
    }
}
