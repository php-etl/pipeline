<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

/**
 * @template Type
 *
 * @extends StreamLoader<Type>
 */
final class DebugLoader extends StreamLoader
{
    /**
     * @param Type|null $line
     */
    protected function formatLine(mixed $line): string
    {
        return var_export($line, true).\PHP_EOL;
    }
}
