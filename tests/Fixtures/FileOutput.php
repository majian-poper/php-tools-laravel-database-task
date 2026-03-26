<?php

namespace PHPTools\LaravelDatabaseTask\Tests\Fixtures;

use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;

class FileOutput extends \SplFileObject implements OutputInterface
{
    public function getValue(): static
    {
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return null;
    }
}
