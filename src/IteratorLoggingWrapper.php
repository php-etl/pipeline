<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline;

use Psr\Log\LoggerInterface;

class IteratorLoggingWrapper implements \Iterator
{
    private ?\ReflectionGenerator $reflectionGenerator = null;

    private ?\ReflectionObject $reflectionObject = null;

    public function __construct(private readonly \Iterator $wrapped, private readonly LoggerInterface $logger)
    {
        if ($this->wrapped instanceof \Generator) {
            try {
                $this->reflectionGenerator = new \ReflectionGenerator($this->wrapped);
            } catch (\ReflectionException $e) {
                throw new \RuntimeException('An error occured during reflection.', 0, $e);
            }
        }

        try {
            $this->reflectionObject = new \ReflectionObject($this->wrapped);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('An error occured during reflection.', 0, $e);
        }
    }

    private function debug(string $calledMethod, \Iterator $iterator, $value = null): void
    {
        try {
            $message = 'Wrapped %type%->%iter% [%object%]: ';
            if (null !== $this->reflectionGenerator) {
                $function = $this->reflectionGenerator->getFunction();
                $functionName = $function->getName();

                if ($function instanceof \ReflectionMethod) {
                    $class = $function->getDeclaringClass();
                    $functionName = $class->getName().'::'.$function->getName();
                }

                $options = [
                    'iter' => $calledMethod,
                    'object' => spl_object_hash($this->wrapped),
                    'function' => var_export($functionName, true),
                    'file' => var_export($this->reflectionGenerator->getExecutingFile(), true),
                    'line' => var_export($this->reflectionGenerator->getExecutingLine(), true),
                    'type' => $this->wrapped instanceof \Generator ? 'generator' : 'iterator',
                ];
            } else {
                $message = 'Wrapped %type%->%iter% [%object%]: [terminated] ';

                $options = [
                    'iter' => $calledMethod,
                    'object' => spl_object_hash($this->wrapped),
                    'type' => $this->wrapped instanceof \Generator ? 'generator' : 'iterator',
                ];
            }
        } catch (\ReflectionException) {
            $message = 'Wrapped %type%->%iter% [%object%]: [terminated] ';

            $options = [
                'iter' => $calledMethod,
                'object' => spl_object_hash($this->wrapped),
                'type' => $this->wrapped instanceof \Generator ? 'generator' : 'iterator',
            ];
        }

        if (2 === \func_num_args()) {
            $options = array_merge(
                $options,
                [
                    'value' => var_export($options, true),
                ]
            );
        }

        $parameters = [];
        $fields = [];
        foreach ($options as $key => $value) {
            if (!\in_array($key, ['type', 'iter', 'object'])) {
                $fields[] = $key.'=%'.$key.'%';
                $parameters['%'.$key.'%'] = var_export($value, true);
            } else {
                $parameters['%'.$key.'%'] = $value;
            }
        }

        $message .= implode(', ', $fields);

        $this->logger->debug(strtr($message, $parameters));
    }

    public function current(): mixed
    {
        $current = $this->wrapped->current();

        $this->debug(__FUNCTION__, $this->wrapped, $current);

        return $current;
    }

    public function next(): void
    {
        $this->wrapped->next();

        $this->debug(__FUNCTION__, $this->wrapped);
    }

    public function key(): mixed
    {
        $key = $this->wrapped->key();

        $this->debug(__FUNCTION__, $this->wrapped, $key);

        return $key;
    }

    public function valid(): bool
    {
        $valid = $this->wrapped->valid();

        $this->debug(__FUNCTION__, $this->wrapped);

        return $valid;
    }

    public function rewind(): void
    {
        $this->wrapped->rewind();

        $this->debug(__FUNCTION__, $this->wrapped);
    }
}
