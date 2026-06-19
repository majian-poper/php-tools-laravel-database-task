<?php

namespace PHPTools\LaravelDatabaseTask\Concerns;

use Filament\Support\Concerns\EvaluatesClosures;

trait InteractsWithBatchable
{
    use EvaluatesClosures;

    protected int | \Closure $batchOrder = 0;

    public function getBatchOrder(): int
    {
        $batchOrder = $this->evaluate($this->batchOrder);

        if (! \is_int($batchOrder) || $batchOrder < 0) {
            throw new \RuntimeException('Batch order must be a non-negative integer.');
        }

        return $batchOrder;
    }

    public function batchOrder(int | \Closure $batchOrder): self
    {
        $this->batchOrder = $batchOrder;

        return $this;
    }
}
