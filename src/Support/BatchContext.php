<?php

namespace PHPTools\LaravelDatabaseTask\Support;

use PHPTools\LaravelDatabaseTask\Contracts\BatchContextInterface;

class BatchContext implements BatchContextInterface
{
    public function __construct(
        protected int $index,
        protected mixed $data
    ) {}

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
