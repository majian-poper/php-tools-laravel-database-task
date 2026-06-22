<?php

namespace PHPTools\LaravelDatabaseTask\Commands;

use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use PHPTools\LaravelDatabaseTask\Contracts;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use PHPTools\LaravelDatabaseTask\Jobs;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

class DispatchApprovedTaskCommand extends Command
{
    protected $signature = 'approved-task:dispatch';

    protected $description = 'Dispatch approved database task jobs.';

    public function handle()
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = DatabaseTaskFacade::resolveModel(DatabaseTask::class)
            ->newQuery()
            ->where('status', TaskStatus::APPROVED)
            ->where(
                static fn(Builder $query) => $query
                    ->whereNull('schedules_at')
                    ->orWhere('schedules_at', '<=', now())
            )
            ->orderBy('id');

        if (filled($databaseTask = (clone $query)->first())) {
            $this->dispatchJob($databaseTask);
        }

        if ((clone $query)->exists()) {
            Artisan::queue('approved-task:dispatch')->delay(5);
        }
    }

    protected function dispatchJob(DatabaseTask $databaseTask): void
    {
        if (blank($task = $databaseTask->toTask())) {
            $this->dispatchFailed($databaseTask, __('database-task::tasks.errors.task_class_not_found'));

            return;
        }

        if (! $databaseTask->moveToStatus(to: TaskStatus::PROCESSING, from: TaskStatus::APPROVED)) {
            $this->dispatchFailed($databaseTask, __('database-task::tasks.errors.task_status_update_failed'));

            return;
        }

        Events\TaskDispatching::dispatch($databaseTask);

        if ($task instanceof Contracts\BatchableTaskInterface) {
            $this->dispatchBatchable($databaseTask);
        } else {
            $this->dispatchNonBatchable($databaseTask);
        }

        Events\TaskDispatchFinished::dispatch($databaseTask);
    }

    protected function dispatchBatchable(DatabaseTask $databaseTask): void
    {
        Bus::batch([])
            ->name($databaseTask->job_name)
            ->before(fn(Batch $batch) => dispatch(new Jobs\DispatchBatchableTask($databaseTask)->withBatchId($batch->id)))
            ->then(fn(Batch $batch) => dispatch((new Jobs\MergeBatchableTask($databaseTask))->withBatchId($batch->id)))
            ->dispatch();
    }

    protected function dispatchNonBatchable(DatabaseTask $databaseTask): void
    {
        Jobs\RunDatabaseTask::dispatch($databaseTask);
    }

    protected function dispatchFailed(DatabaseTask $databaseTask, string $reason): void
    {
        $databaseTask->moveToFailedStatus($reason);

        Events\TaskDispatchFailed::dispatch($databaseTask, new \RuntimeException($reason));
    }
}
