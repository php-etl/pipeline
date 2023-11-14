<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Bucket\AcceptanceResultBucketInterface;
use Kiboko\Contract\Bucket\RejectionResultBucketInterface;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\StepRejectionInterface;
use Kiboko\Contract\Pipeline\StepStateInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class PipelineRunner implements PipelineRunnerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly LogLevel|string $rejectionLevel = LogLevel::WARNING
    ) {}

    /**
     * @template InputType
     * @template OutputType
     *
     * @param \Iterator<int<0, max>, InputType|null>                                                                                                                                $source
     * @param \Generator<int, ResultBucketInterface<OutputType>|AcceptanceResultBucketInterface<InputType>|RejectionResultBucketInterface<InputType>|null, InputType, void> $coroutine
     * @param StepRejectionInterface<InputType>                                                                                                                                     $rejection
     *
     * @return \Iterator<int<0, max>, ResultBucketInterface<OutputType>>
     */
    public function run(
        \Iterator $source,
        \Generator $coroutine,
        StepRejectionInterface $rejection,
        StepStateInterface $state,
    ): \Iterator {
        $wrapper = new GeneratorWrapper();
        $wrapper->rewind($source, $coroutine);

        while ($wrapper->valid($source)) {
            $bucket = $coroutine->send($source->current());

            if (null === $bucket) {
                break;
            }

            if (!$bucket instanceof ResultBucketInterface) {
                throw UnexpectedYieldedValueType::expectingTypes($coroutine, [ResultBucketInterface::class, AcceptanceResultBucketInterface::class, RejectionResultBucketInterface::class], $bucket);
            }

            if ($bucket instanceof RejectionResultBucketInterface) {
                $reasons = $bucket->reasons();
                foreach ($bucket->walkRejection() as $line) {
                    if (null !== $reasons) {
                        $rejection->rejectWithReason($line, implode(\PHP_EOL, $reasons));
                    } else {
                        $rejection->reject($line);
                    }
                    $state->reject();

                    $this->logger->log(
                        $this->rejectionLevel,
                        'Some data was rejected from the pipeline',
                        [
                            'line' => $line,
                        ]
                    );
                }
            }

            if ($bucket instanceof AcceptanceResultBucketInterface) {
                yield from $bucket->walkAcceptance();
                $state->accept();
            }

            $wrapper->next($source);
        }
    }
}
