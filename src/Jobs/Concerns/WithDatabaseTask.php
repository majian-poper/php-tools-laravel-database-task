<?php

namespace PHPTools\LaravelDatabaseTask\Jobs\Concerns;

use Illuminate\Queue\Middleware\Skip;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

trait WithDatabaseTask
{
    public ?DatabaseTask $databaseTask = null;

    public function setDatabaseTask(DatabaseTask $databaseTask): void
    {
        $this->databaseTask = $databaseTask;
    }

    public function getDatabaseTask(): ?DatabaseTask
    {
        return $this->databaseTask;
    }

    public function isProcessing(): bool
    {
        return $this->databaseTask?->status === TaskStatus::PROCESSING;
    }
}
