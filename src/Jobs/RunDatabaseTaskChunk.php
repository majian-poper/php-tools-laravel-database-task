<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTaskBatch;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTaskOutput;
use PHPTools\LaravelDatabaseTask\Outputs\TextOutput;
use PHPTools\LaravelDatabaseTask\Support\BatchContext;

class RunDatabaseTaskChunk implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        public DatabaseTask $task,
        public int $index,
        public mixed $data
    ) {}

    public function displayName(): string
    {
        return \sprintf(
            '%s #%d-%d (%s)',
            class_basename($this),
            $this->task->getKey(),
            $this->index,
            $this->task->task_class,
        );
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        /** @var DatabaseTaskBatch $batch */
        $batch = $this->task->batches()->updateOrCreate(
            ['batch_index' => $this->index],
            [
                'context' => $this->data,
                'status' => TaskStatus::PROCESSING,
            ]
        );

        try {
            DB::transaction(fn() => $this->runTask($this->task, $batch));
        } catch (\Throwable $e) {
            $this->runTaskFailed($this->task, $batch, $e);
        }
    }

    protected function runTask(DatabaseTask $task, DatabaseTaskBatch $batch): void
    {
        $output = $task->run(new BatchContext($this->index, $this->data));

        $outputValue = $output->getValue();

        $isFile = $outputValue instanceof \SplFileObject;

        /** @var DatabaseTaskOutput $databaseTaskOutput */
        $databaseTaskOutput = $task->outputs()->create(
            [
                'output_class' => \get_class($output),
                'output_value' => $isFile ? '' : $outputValue,
                'is_file' => $isFile,
                'expires_at' => $output->getExpiresAt(),
            ]
        );

        if ($isFile) {
            $databaseTaskOutput->addMedia($outputValue->getRealPath())->toMediaCollection();
        }

        $batch->markAs(TaskStatus::PROCESSED)
            ->setAttribute('database_task_output_id', $databaseTaskOutput->id)
            ->save();
    }

    protected function runTaskFailed(DatabaseTask $task, DatabaseTaskBatch $batch, \Throwable $e): void
    {
        $task->outputs()->create(
            [
                'output_class' => TextOutput::class,
                'output_value' => $e->getMessage(),
                'is_file' => false,
                'expires_at' => null,
            ]
        );

        $batch->markAs(TaskStatus::FAILED)->save();

        throw $e;
    }
}
