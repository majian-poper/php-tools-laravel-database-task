<?php

namespace PHPTools\LaravelDatabaseTask\Contracts;

interface OutputInterface
{
    public function getValue(): string | \SplFileObject;

    public function getExpiresAt(): ?\DateTimeInterface;
}
