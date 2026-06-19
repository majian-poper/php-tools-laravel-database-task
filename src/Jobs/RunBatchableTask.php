<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\Skip;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableOutput;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Contracts\TaskInterface;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

class RunBatchableTask implements ShouldQueue
{
    use Batchable;
    use Concerns\WithDatabaseTask;
    use Dispatchable;
    use Queueable;

    public $timeout = 300; // 5 minutes

    public function __construct(DatabaseTask $databaseTask, public int $batchOrder = 0)
    {
        $this->setDatabaseTask($databaseTask);
    }

    public function displayName(): string
    {
        return \sprintf('%s.%d.run', $this->databaseTask->job_name, $this->batchOrder);
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
            if (! $task instanceof TaskInterface) {
                throw new \RuntimeException('Task is not valid.');
            }

            $output = $task->run(...$databaseTask->getInputsForBatch($this->batchOrder));
        } catch (\Throwable $e) {
            $this->batch()->cancel();

            $databaseTask->updateStatusFailed($e->getMessage());

            return;
        }

        $this->saveOutput($databaseTask, $output);

        $this->dispatchMergeJobIfNeeded($databaseTask);
    }

    protected function saveOutput(DatabaseTask $databaseTask, OutputInterface $output): void
    {
        $outputValue = $output->getValue();

        $batchOrder = $output instanceof BatchableOutput ? $output->getBatchOrder() : 0;

        $databaseTask->batch_outputs()->where('batch_order', $batchOrder)->get()->map->delete();

        $databaseTaskOutput = DatabaseTaskFacade::fromOutput($output, $databaseTask);

        $databaseTaskOutput->save();

        if ($outputValue instanceof \SplFileObject && $outputValue->isReadable()) {
            $databaseTaskOutput->addMedia($outputValue->getRealPath())->toMediaCollection();
        }
    }

    protected function dispatchMergeJobIfNeeded(DatabaseTask $databaseTask): void
    {
        $databaseTask->loadCount('batch_inputs', 'batch_outputs');

        $batchInputsCount = $databaseTask->batch_inputs_count;
        $batchOutputsCount = $databaseTask->batch_outputs_count;

        if ($batchOutputsCount === 0 || $batchInputsCount !== $batchOutputsCount) {
            return;
        }

        Events\BatchableTaskFinished::dispatch($databaseTask);

        $this->batch()->add(new MergeBatchableTask($databaseTask));
    }
}
