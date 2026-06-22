<?php

namespace PHPTools\LaravelDatabaseTask\Jobs\Concerns;

use Illuminate\Queue\Middleware\Skip;
use PHPTools\LaravelDatabaseTask\Contracts\TaskInterface;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

trait WithTask
{
    public readonly DatabaseTask $databaseTask;

    public $timeout = 300; // 5 minutes

    public function middleware(): array
    {
        return [Skip::when($this->shouldSkip())];
    }

    public function setDatabaseTask(DatabaseTask $databaseTask): void
    {
        $this->databaseTask = $databaseTask;
    }

    public function getDatabaseTask(): DatabaseTask
    {
        return $this->databaseTask;
    }

    public function isProcessing(): bool
    {
        return $this->databaseTask->status === TaskStatus::PROCESSING;
    }

    public function getTask(): ?TaskInterface
    {
        $task = $this->databaseTask->toTask();

        if (! $task instanceof TaskInterface) {
            throw new \RuntimeException(
                __(
                    'database-task::tasks.errors.task_class_not_found',
                    ['task_class' => $this->databaseTask->task_class]
                )
            );
        }

        return $task;
    }

    protected function markAsFailed(DatabaseTask $databaseTask, string $reason): void
    {
        $databaseTask->moveToFailedStatus($reason);
    }

    protected function shouldSkip()
    {
        return ! $this->isProcessing();
    }
}
