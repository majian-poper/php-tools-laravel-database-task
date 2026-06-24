<?php

namespace PHPTools\LaravelDatabaseTask\Concerns\Output;

trait AsFileOutput
{
    use HasExpires;
    use HasValue;

    public function getValue(): ?\SplFileObject
    {
        $value = parent::getValue();

        if ($value instanceof \SplFileObject) {
            return $value;
        }

        return null;
    }
}
