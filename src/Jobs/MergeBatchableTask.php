<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\Skip;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableTaskInterface;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

class MergeBatchableTask implements ShouldQueue
{
    use Batchable;
    use Concerns\WithDatabaseTask;
    use Dispatchable;
    use Queueable;

    public $timeout = 300; // 5 minutes

    public function __construct(DatabaseTask $databaseTask)
    {
        $this->setDatabaseTask($databaseTask);
    }

    public function displayName(): string
    {
        return \sprintf('%s.merge', $this->databaseTask->job_name);
    }

    public function middleware(): array
    {
        return [Skip::unless($this->batching() && $this->isProcessing())];
    }

    public function handle(): void
    {
        $databaseTask = $this->getDatabaseTask();
        $task = $databaseTask->toTask();

        try {
            if (! $task instanceof BatchableTaskInterface) {
                throw new \RuntimeException('Task is not batchable.');
            }

            $mergedOutput = $task->mergeBatchableOutputs(...$databaseTask->getBatchableOutputs());
        } catch (\Throwable $e) {
            $this->batch()->cancel();

            $databaseTask->updateStatusFailed($e->getMessage());

            return;
        }

        $this->saveOutput($databaseTask, $mergedOutput);
    }

    protected function saveOutput(DatabaseTask $databaseTask, OutputInterface $mergedOutput): void
    {
        $outputValue = $mergedOutput->getValue();

        // Delete existing outputs for the non-batchable outputs (batch_order = 0)
        $databaseTask->outputs()->get()->map->delete();

        $databaseTaskOutput = DatabaseTaskFacade::fromOutput($mergedOutput, $databaseTask);

        $databaseTaskOutput->save();

        if ($outputValue instanceof \SplFileObject && $outputValue->isReadable()) {
            $databaseTaskOutput->addMedia($outputValue->getRealPath())->toMediaCollection();
        }

        $databaseTask->updateStatus(TaskStatus::PROCESSED, TaskStatus::PROCESSING);

        Events\BatchableTaskMerged::dispatch($databaseTask);
    }
}
