<?php

namespace PHPTools\LaravelDatabaseTask\Outputs;

use PHPTools\LaravelDatabaseTask\Concerns;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;

class FileOutput extends \SplFileObject implements OutputInterface
{
    use Concerns\InteractsWithOutput;

    public function __destruct()
    {
        @\unlink($this->getRealPath());
    }

    public function getValue(): \SplFileObject
    {
        return $this;
    }
}
