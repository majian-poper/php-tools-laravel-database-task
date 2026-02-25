<?php

namespace PHPTools\LaravelDatabaseTask\Concerns;

use Filament\Support\Concerns\EvaluatesClosures;

trait InteractsWithOutput
{
    use EvaluatesClosures;

    protected null | string | \SplFileObject | \Closure $value = null;

    protected null | \DateTimeInterface | \Closure $expiresAt = null;

    // --- Basic Methods ---

    public function getValue(): string | \SplFileObject
    {
        return $this->evaluate($this->value);
    }

    public function value(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(\DateTimeInterface | \Closure $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->evaluate($this->expiresAt);
    }
}
