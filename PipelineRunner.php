<?php

namespace Kiboko\Component\ETL\Pipeline;

use Kiboko\Component\ETL\Contracts\AcceptanceResultBucketInterface;
use Kiboko\Component\ETL\Contracts\PipelineRunnerInterface;
use Kiboko\Component\ETL\Contracts\RejectionResultBucketInterface;
use Kiboko\Component\ETL\Contracts\ResultBucketInterface;
use Kiboko\Component\ETL\Core\Exception\UnexpectedYieldedValueType;
use Kiboko\Component\ETL\Core\Iterator\ResumableIterator;

class PipelineRunner implements PipelineRunnerInterface
{
    /**
     * @param \Iterator  $source
     * @param \Generator $coroutine
     *
     * @return \Iterator
     */
    public function run(\Iterator $source, \Generator $coroutine): \Iterator
    {
        return new ResumableIterator(function(\Iterator $source) use($coroutine) {
            $wrapper = new GeneratorWrapper();
            $wrapper->rewind($source, $coroutine);

            while ($wrapper->valid($source)) {
                $bucket = $coroutine->send($source->current());

                if (!$bucket instanceof ResultBucketInterface) {
                    throw UnexpectedYieldedValueType::expectingType(
                        $coroutine,
                        ResultBucketInterface::class,
                        $bucket
                    );
                }

                if ($bucket instanceof RejectionResultBucketInterface) {
                    // TODO: handle the rejection pipeline
                }

                if (!$bucket instanceof AcceptanceResultBucketInterface) {
                    throw UnexpectedYieldedValueType::expectingType(
                        $coroutine,
                        AcceptanceResultBucketInterface::class,
                        $bucket
                    );
                }

                yield from $bucket->walkAcceptance();

                $wrapper->next($source, $coroutine);
            }
        }, $source);
    }
}
