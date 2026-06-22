<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableOutput;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

class MergeBatchableTask implements ShouldQueue
{
    use Concerns\WithBatchableTask;
    use Queueable;

    public function __construct(DatabaseTask $databaseTask)
    {
        $this->setDatabaseTask($databaseTask);
    }

    public function displayName(): string
    {
        return \sprintf('%s.merge', $this->databaseTask->job_name);
    }

    public function handle(): void
    {
        $databaseTask = $this->getDatabaseTask();

        Events\BatchableTaskMerging::dispatch($databaseTask);

        try {
            $task = $this->getBatchableTask();

            $databaseTask->getConnection()->transaction(
                fn() => $this->saveOutput(
                    $databaseTask,
                    $task->mergeBatchableOutputs(...$databaseTask->getBatchableOutputs())
                )
            );
        } catch (\Throwable $e) {
            $this->markAsFailed($databaseTask, $e->getMessage());

            Events\BatchableTaskMergeFailed::dispatch($databaseTask, $e);
        }
    }

    protected function saveOutput(DatabaseTask $databaseTask, OutputInterface $mergedOutput): void
    {
        if ($mergedOutput instanceof BatchableOutput && $mergedOutput->getBatchOrder() !== 0) {
            throw new \RuntimeException(__('database-task::tasks.errors.output_should_not_be_batchable'));
        }

        $databaseTask->moveToProcessedStatus($mergedOutput);

        Events\BatchableTaskMergeFinished::dispatch($databaseTask);
    }
}
