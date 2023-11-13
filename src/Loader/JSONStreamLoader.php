<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

/**
 * @template Type
 *
 * @extends StreamLoader<Type>
 */
final class JSONStreamLoader extends StreamLoader
{
    /**
     * @param Type|null $line
     */
    protected function formatLine(mixed $line): string
    {
        return json_encode($line, \JSON_THROW_ON_ERROR).\PHP_EOL;
    }
}
