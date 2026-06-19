<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\Skip;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableOutput;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Contracts\TaskInterface;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTaskOutput;

class RunDatabaseTask implements ShouldQueue
{
    use Batchable;
    use Concerns\WithDatabaseTask;
    use Queueable;

    public $timeout = 300; // 5 minutes

    public function __construct(DatabaseTask $databaseTask, public int $batchOrder = 0)
    {
        $this->setDatabaseTask($databaseTask);
    }

    public function displayName(): string
    {
        return \sprintf('%s.%d.run', $this->databaseTask->batch_name, $this->batchOrder);
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

            $output = $task->run(...$databaseTask->getBatchInputs($this->batchOrder));
        } catch (\Throwable $e) {
            $this->batch()->cancel();

            $databaseTask->updateStatusFailed($e->getMessage());

            return;
        }

        $this->saveOutput($databaseTask, $output);
    }

    protected function saveOutput(DatabaseTask $databaseTask, OutputInterface $output): void
    {
        $outputValue = $output->getValue();
        $batchOrder = $output instanceof BatchableOutput ? $output->getBatchOrder() : 0;

        $databaseTask->outputs($batchOrder)->delete();

        /** @var \PHPTools\LaravelDatabaseTask\Models\DatabaseTaskOutput $databaseTaskOutput */
        $databaseTaskOutput = tap(DatabaseTaskOutput::fromOutput($output, $databaseTask))->save();

        if ($outputValue instanceof \SplFileObject && $outputValue->isReadable()) {
            $databaseTaskOutput->addMedia($outputValue->getRealPath())->toMediaCollection();
        }
    }
}
