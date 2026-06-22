<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableOutput;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

class RunBatchableTask implements ShouldQueue
{
    use Concerns\WithBatchableTask;
    use Queueable;

    public function __construct(DatabaseTask $databaseTask, public int $batchOrder = 0)
    {
        $this->setDatabaseTask($databaseTask);
    }

    public function displayName(): string
    {
        return \sprintf('%s.%d.run', $this->databaseTask->job_name, $this->batchOrder);
    }

    public function handle(): void
    {
        $databaseTask = $this->getDatabaseTask();

        Events\BatchableTaskRunning::dispatch($databaseTask, $this->batchOrder);

        try {
            $task = $this->getBatchableTask();

            $databaseTask->getConnection()->transaction(
                fn() => $this->saveBatchableOutput(
                    $databaseTask,
                    $task->run(...$databaseTask->getInputsForBatch($this->batchOrder))
                )
            );
        } catch (\Throwable $e) {
            $this->markAsFailed($databaseTask, $e->getMessage());

            Events\BatchableTaskRunFailed::dispatch($databaseTask, $this->batchOrder, $e);
        }
    }

    protected function saveBatchableOutput(DatabaseTask $databaseTask, OutputInterface $output): void
    {
        if (! $output instanceof BatchableOutput) {
            throw new \RuntimeException(__('database-task::tasks.errors.output_not_batchable'));
        }

        if ($output->getBatchOrder() !== $this->batchOrder) {
            throw new \RuntimeException(__('database-task::tasks.errors.output_batch_order_mismatch'));
        }

        $databaseTask->saveOutput($output);

        Events\BatchableTaskRunFinished::dispatch($databaseTask, $this->batchOrder);
    }
}
