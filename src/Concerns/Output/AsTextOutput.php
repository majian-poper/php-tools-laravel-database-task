<?php

namespace PHPTools\LaravelDatabaseTask\Concerns\Output;

trait AsTextOutput
{
    use HasExpires;
    use HasValue;

    public function getValue(): string
    {
        $value = parent::getValue();

        if (\is_string($value)) {
            return $value;
        }

        return '';
    }
}
