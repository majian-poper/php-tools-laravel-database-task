<?php

namespace PHPTools\LaravelDatabaseTask\Outputs;

use PHPTools\LaravelDatabaseTask\Concerns;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;

class FileOutput extends \SplFileObject implements OutputInterface
{
    use Concerns\InteractsWithOutput;

    protected bool $autoDelete = true;

    public function __destruct()
    {
        if ($this->autoDelete) {
            @\unlink($this->getRealPath());
        }
    }

    public function getValue(): \SplFileObject
    {
        return $this;
    }

    public function autoClean(bool $autoClean = true): static
    {
        $this->autoDelete = $autoClean;

        return $this;
    }
}
