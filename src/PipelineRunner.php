<?php

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Bucket\AcceptanceResultBucketInterface;
use Kiboko\Contract\Bucket\RejectionResultBucketInterface;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RejectionInterface;
use Kiboko\Contract\Pipeline\StateInterface;
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

    public function run(
        \Iterator $source,
        \Generator $coroutine,
        RejectionInterface $rejection,
        StateInterface $state,
    ): \Iterator {
        $state->initialize();
        $rejection->initialize();

        yield;
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
                foreach ($bucket->walkRejection() as $line) {
                    $rejection->reject($line);
                    $state->reject();

                    $this->logger->log(
                        $this->rejectionLevel,
                        'Some data was rejected from the pipeline',
                        [
                            'line' => $line
                        ]
                    );
                }
            }

            if (!$bucket instanceof ResultBucketInterface) {
                throw UnexpectedYieldedValueType::expectingTypes(
                    $coroutine,
                    [
                        ResultBucketInterface::class,
                        AcceptanceResultBucketInterface::class,
                        RejectionResultBucketInterface::class,
                    ],
                    $bucket
                );
            }

            if ($bucket instanceof AcceptanceResultBucketInterface) {
                yield from $bucket->walkAcceptance();
                $state->accept();
            }

            $wrapper->next($source);
        }

        $state->teardown();
        $rejection->teardown();
    }
}
