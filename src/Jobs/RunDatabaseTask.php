<?php

namespace PHPTools\LaravelDatabaseTask\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;
use PHPTools\LaravelDatabaseTask\Outputs\TextOutput;

class RunDatabaseTask implements ShouldQueue
{
    use Queueable;

    public function __construct(public DatabaseTask $task) {}

    public function displayName(): string
    {
        return \sprintf(
            '%s #%d (%s)',
            \get_class($this),
            $this->task->getKey(),
            $this->task->task_class,
        );
    }

    public function handle(): void
    {
        $this->task->markAs(TaskStatus::PROCESSING)->save();

        $this->task->outputs()->delete();

        try {
            DB::transaction(fn() => $this->runTask($this->task));
        } catch (\Throwable $e) {
            $this->runTaskFailed($this->task, $e);
        }
    }

    protected function runTask(DatabaseTask $task): void
    {
        $output = $task->run();

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

        $task->markAs(TaskStatus::PROCESSED)->save();
    }

    protected function runTaskFailed(DatabaseTask $task, \Throwable $e): void
    {
        $task->outputs()->create(
            [
                'output_class' => TextOutput::class,
                'output_value' => $e->getMessage(),
                'is_file' => false,
                'expires_at' => null,
            ]
        );

        $task->markAs(TaskStatus::FAILED)->save();
    }
}
