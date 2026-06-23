<?php

namespace PHPTools\LaravelDatabaseTask\Outputs;

use PHPTools\LaravelDatabaseTask\Concerns;
use PHPTools\LaravelDatabaseTask\Contracts;

class FileOutput extends \SplFileObject implements Contracts\OutputInterface
{
    use Concerns\InteractsWithOutput {
        getValue as baseGetValue;
    }

    protected bool $autoDelete = true;

    public function __destruct()
    {
        if ($this->autoDelete && $this->isWritable()) {
            @\unlink($this->getRealPath());
        }
    }

    public function getValue(): \SplFileObject
    {
        if (isset($this->value)) {
            return $this->baseGetValue();
        }

        return $this;
    }

    public function autoClean(bool $autoClean = true): static
    {
        $this->autoDelete = $autoClean;

        return $this;
    }
}
