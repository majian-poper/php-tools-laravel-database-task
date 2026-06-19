<?php

namespace PHPTools\LaravelDatabaseTask\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use PHPTools\LaravelDatabaseTask\Contracts;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use PHPTools\LaravelDatabaseTask\Jobs;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

class DispatchApprovedTaskCommand extends Command
{
    protected $signature = 'approved-task:dispatch';

    protected $description = 'Dispatch approved database task jobs.';

    public function handle()
    {
        $query = DatabaseTaskFacade::resolveModel(DatabaseTask::class)
            ->newQuery()
            ->where('status', TaskStatus::APPROVED)
            ->where(
                static fn(Builder $query) => $query
                    ->whereNull('schedules_at')
                    ->orWhere('schedules_at', '<=', now())
            );

        (clone $query)->take(5)->get()->each($this->dispatchJob(...));

        if ((clone $query)->exists()) {
            Artisan::queue('approved-task:dispatch')->delay(5);
        }
    }

    protected function dispatchJob(DatabaseTask $databaseTask): void
    {
        $task = $databaseTask->toTask();

        if (\is_null($task)) {
            $databaseTask->updateStatusFailed('Task class not found.');

            return;
        }

        if (! $task instanceof Contracts\BatchableTaskInterface) {
            Jobs\RunDatabaseTask::dispatch($databaseTask);

            return;
        }

        Bus::batch([new Jobs\DispatchBatchableTask($databaseTask)])
            ->name($databaseTask->job_name)
            ->dispatchIf($databaseTask->updateStatus(to: TaskStatus::PROCESSING, from: TaskStatus::APPROVED));
    }
}
