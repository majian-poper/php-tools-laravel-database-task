<?php

namespace PHPTools\LaravelDatabaseTask\Outputs;

class NullOutput extends TextOutput
{
    public function getValue(): string
    {
        return '';
    }
}
