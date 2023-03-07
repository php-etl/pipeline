<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline\Loader;

final class DebugLoader extends StreamLoader
{
    protected function formatLine($line)
    {
        return var_export($line, true).\PHP_EOL;
    }
}
