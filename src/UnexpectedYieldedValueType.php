<?php

namespace Kiboko\Component\Pipeline;

final class UnexpectedYieldedValueType extends \UnexpectedValueException
{
    public function __construct(private \Generator|\Fiber $async, string $message = null, int $code = null, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getAsync(): \Generator|\Fiber
    {
        return $this->async;
    }

    public static function expectingTypes(\Generator|\Fiber $async, array $expectedTypes, $actual, int $code = null, ?\Exception $previous = null): self
    {
        if ($async instanceof \Generator) {
            try {
                $re = new \ReflectionGenerator($async);

                $function = $re->getFunction();
                $functionName = $function->getName();

                if ($function instanceof \ReflectionMethod) {
                    $class = $function->getDeclaringClass();
                    $functionName = $class->getName() . '::' . $functionName;
                }
                $executionFile = $re->getExecutingFile();
                $executionLine = $re->getExecutingLine();

                return new self(
                    $async,
                    strtr(
                        'Invalid yielded data, was expecting %expected%, got %actual%. Coroutine declared in %function%, running in %file%:%line%.',
                        [
                            '%expected%' => implode(' or ', $expectedTypes),
                            '%actual%' => is_object($actual) ? get_class($actual) : gettype($actual),
                            '%function%' => $functionName,
                            '%file%' => $executionFile,
                            '%line%' => $executionLine,
                        ]
                    ),
                    $code,
                    $previous
                );
            } catch (\ReflectionException) {
                return new self(
                    $async,
                    strtr(
                        'Invalid yielded data, was expecting %expected%, got %actual%. Coroutine was declared in a terminated generator, could not fetch the declaration metadata.',
                        [
                            '%expected%' => implode(' or ', $expectedTypes),
                            '%actual%' => is_object($actual) ? get_class($actual) : gettype($actual),
                        ]
                    ),
                    $code,
                    $previous
                );
            }
        }

        if ($async instanceof \Fiber) {
            $re = new \ReflectionFiber($async);

            $executionFile = $re->getExecutingFile();
            $executionLine = $re->getExecutingLine();

            return new self(
                $async,
                strtr(
                    'Invalid fiber data, was expecting %expected%, got %actual%. Fiber suspended in %file%:%line%.',
                    [
                        '%expected%' => implode(' or ', $expectedTypes),
                        '%actual%' => is_object($actual) ? get_class($actual) : gettype($actual),
                        '%file%' => $executionFile,
                        '%line%' => $executionLine,
                    ]
                ),
                $code,
                $previous
            );
        }

        return new self(
            $async,
            strtr(
                'Invalid data, was expecting %expected%, got %actual%. Could not determine the current execution location.',
                [
                    '%expected%' => implode(' or ', $expectedTypes),
                    '%actual%' => is_object($actual) ? get_class($actual) : gettype($actual),
                ]
            ),
            $code,
            $previous
        );
    }
}
