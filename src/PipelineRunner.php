<?php

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Bucket\AcceptanceResultBucketInterface;
use Kiboko\Contract\Bucket\RejectionResultBucketInterface;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class PipelineRunner implements PipelineRunnerInterface
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger, private string $rejectionLevel = LogLevel::WARNING)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param \Iterator  $source
     * @param \Generator $coroutine
     *
     * @return \Iterator
     */
    public function run(\Iterator $source, \Generator $coroutine): \Iterator
    {
        return new ResumableIterator(function (\Iterator $source) use ($coroutine) {
            $wrapper = new GeneratorWrapper();
            $wrapper->rewind($source, $coroutine);

            while ($wrapper->valid($source)) {
                $bucket = $coroutine->send($source->current());

                if ($bucket === null) {
                    break;
                }

                if (!$bucket instanceof ResultBucketInterface) {
                    throw UnexpectedYieldedValueType::expectingTypes(
                        $coroutine,
                        [ResultBucketInterface::class],
                        $bucket
                    );
                }

                if ($bucket instanceof RejectionResultBucketInterface) {
                    foreach ($bucket as $rejection) {
                        $this->logger->log(
                            $this->rejectionLevel,
                            'Some data was rejected from the pipeline',
                            [
                                'line' => $rejection
                            ]
                        );
                    }
                }

                if (!$bucket instanceof AcceptanceResultBucketInterface
                    && !$bucket instanceof RejectionResultBucketInterface
                ) {
                    throw UnexpectedYieldedValueType::expectingTypes(
                        $coroutine,
                        [
                            AcceptanceResultBucketInterface::class,
                            RejectionResultBucketInterface::class,
                        ],
                        $bucket
                    );
                }

                if ($bucket instanceof AcceptanceResultBucketInterface) {
                    yield from $bucket->walkAcceptance();
                }

                $wrapper->next($source, $coroutine);
            }
        }, $source);
    }
}
