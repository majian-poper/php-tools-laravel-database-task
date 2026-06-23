<?php

namespace PHPTools\LaravelDatabaseTask\Outputs;

use PHPTools\LaravelDatabaseTask\Concerns;
use PHPTools\LaravelDatabaseTask\Contracts;

class TextOutput implements Contracts\OutputInterface
{
    use Concerns\InteractsWithOutput;

    public function __construct(string $text = '')
    {
        $this->value($text);
    }

    public function getValue(): string
    {
        return (string) $this->evaluate($this->value);
    }
}
