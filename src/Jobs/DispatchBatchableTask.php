<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableInput;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTaskInput;

class DispatchBatchableTask implements ShouldQueue
{
    use Concerns\WithBatchableTask;
    use Queueable;

    public function __construct(DatabaseTask $databaseTask)
    {
        $this->setDatabaseTask($databaseTask);
    }

    public function displayName(): string
    {
        return \sprintf('%s.dispatch-batch', $this->databaseTask->job_name);
    }

    public function handle(): void
    {
        $databaseTask = $this->getDatabaseTask();

        Events\BatchableTaskDispatching::dispatch($databaseTask);

        try {
            $task = $this->getBatchableTask();

            $databaseTask->getConnection()->transaction(
                fn() => $this->saveBatchableInputs(
                    $databaseTask,
                    $task->getBatchableInputs(...$databaseTask->getNonBatchableInputs())
                )
            );
        } catch (\Throwable $e) {
            $this->markAsFailed($databaseTask, $e->getMessage());

            Events\BatchableTaskDispatchFailed::dispatch($databaseTask, $e);
        }
    }

    protected function saveBatchableInputs(DatabaseTask $databaseTask, iterable $batchableInputs): void
    {
        $batchableInputValues = [];
        $jobs = [];

        foreach ($batchableInputs as $batchInput) {
            if ($batchInput instanceof BatchableInput) {
                $batchableInputValues[] = DatabaseTaskFacade::resolveModel(DatabaseTaskInput::class)
                    ->fromInput($batchInput, $databaseTask)
                    ->updateTimestamps()
                    ->getAttributes();

                $jobs[] = new RunBatchableTask($databaseTask, $batchInput->getBatchOrder());
            }
        }

        if (blank($jobs)) {
            throw new \RuntimeException(__('database-task::tasks.errors.no_data'));
        }

        $databaseTask->outputs()->where('batch_order', '>', 0)->delete();
        $databaseTask->inputs()->where('batch_order', '>', 0)->delete();
        $databaseTask->inputs()->insert($batchableInputValues);

        $this->batch()->add($jobs);

        Events\BatchableTaskDispatchFinished::dispatch($databaseTask);
    }
}
