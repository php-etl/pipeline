<?php

namespace Kiboko\Component\Pipeline\Loader;

final class StderrLoader extends StreamLoader
{
    public function __construct()
    {
        parent::__construct(STDOUT);
    }

    protected function formatLine($line)
    {
        return var_export($line, true) . PHP_EOL;
    }
}
