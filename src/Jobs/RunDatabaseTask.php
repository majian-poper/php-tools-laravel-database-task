<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\Skip;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableOutput;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

class RunDatabaseTask implements ShouldQueue
{
    use Concerns\WithTask;
    use Queueable;

    public function __construct(DatabaseTask $databaseTask)
    {
        $this->setDatabaseTask($databaseTask);
    }

    public function displayName(): string
    {
        return \sprintf('%s.run', $this->databaseTask->job_name);
    }

    public function middleware(): array
    {
        return [Skip::unless($this->isProcessing())];
    }

    public function handle(): void
    {
        $databaseTask = $this->getDatabaseTask();

        Events\TaskRunning::dispatch($databaseTask);

        try {
            $task = $this->getTask();

            $databaseTask->getConnection()->transaction(
                fn() => $this->saveOutput(
                    $databaseTask,
                    $task->run(...$databaseTask->getNonBatchableInputs())
                )
            );
        } catch (\Throwable $e) {
            $this->markAsFailed($databaseTask, $e->getMessage());

            Events\TaskRunFailed::dispatch($databaseTask, $e);
        }
    }

    protected function saveOutput(DatabaseTask $databaseTask, OutputInterface $mergedOutput): void
    {
        if ($mergedOutput instanceof BatchableOutput && $mergedOutput->getBatchOrder() !== 0) {
            throw new \RuntimeException(__('database-task::tasks.errors.output_should_not_be_batchable'));
        }

        $databaseTask->moveToProcessedStatus($mergedOutput);

        Events\TaskRunFinished::dispatch($databaseTask);
    }
}
