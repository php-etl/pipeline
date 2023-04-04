<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

final class JSONStreamLoader extends StreamLoader
{
    protected function formatLine($line)
    {
        return json_encode($line, \JSON_THROW_ON_ERROR).\PHP_EOL;
    }
}
