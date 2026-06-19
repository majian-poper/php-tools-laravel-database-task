<?php

namespace PHPTools\LaravelDatabaseTask\Outputs;

use PHPTools\LaravelDatabaseTask\Concerns;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableOutput;

class TextOutput implements BatchableOutput
{
    use Concerns\InteractsWithBatchable;
    use Concerns\InteractsWithOutput;

    public function __construct(string $text = '', int $batchOrder = 0)
    {
        $this->value($text)->batchOrder($batchOrder);
    }

    public function getValue(): string
    {
        return (string) $this->evaluate($this->value);
    }
}
