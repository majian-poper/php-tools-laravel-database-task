<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\Skip;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableInput;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableTaskInterface;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTaskInput;

class DispatchBatchableDatabaseTask extends RunDatabaseTask implements ShouldQueue
{
    use Batchable;
    use Concerns\WithDatabaseTask;
    use Queueable;

    public function __construct(DatabaseTask $databaseTask)
    {
        $this->setDatabaseTask($databaseTask);
    }

    public function displayName(): string
    {
        return \sprintf('%s.dispatch-batch', $this->databaseTask->batch_name);
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

            $batchableInputs = $task->getBatchableInputs(...$databaseTask->getInputs());
        } catch (\Throwable $e) {
            $this->batch()->cancel();

            $databaseTask->updateStatusFailed($e->getMessage());

            return;
        }

        $this->saveBatchableInputs($databaseTask, $batchableInputs);
    }

    protected function saveBatchableInputs(DatabaseTask $databaseTask, iterable $batchableInputs): void
    {
        $inputValues = [];
        $jobs = [];

        foreach ($batchableInputs as $batchInput) {
            if ($batchInput instanceof BatchableInput) {
                $inputValues[] = DatabaseTaskInput::fromInput($batchInput, $databaseTask)->getAttributes();

                $jobs[] = new RunDatabaseTask($databaseTask, $batchInput->getBatchOrder());
            }
        }

        $databaseTask->batch_outputs()->delete();
        $databaseTask->batch_inputs()->delete();

        $databaseTask->inputs()->insert($inputValues);

        $this->batch()->add($jobs);
    }
}
