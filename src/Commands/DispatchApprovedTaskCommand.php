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
    protected $signature = 'approved-database-task:dispatch';

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
            Artisan::queue('approved-database-task:dispatch')->delay(5);
        }
    }

    protected function dispatchJob(DatabaseTask $databaseTask): void
    {
        $task = DatabaseTaskFacade::toTask($databaseTask);

        if (\is_null($task)) {
            // TODO: 写入 output 记录原因
            $databaseTask->updateStatus(to: TaskStatus::FAILED);

            return;
        }

        $job = $task instanceof Contracts\BatchableTaskInterface
            ? new Jobs\DispatchBatchDatabaseTask($databaseTask)
            : new Jobs\RunDatabaseTask($databaseTask);

        Bus::batch([$job])
            ->name($databaseTask->batch_name)
            ->dispatchIf($databaseTask->updateStatus(to: TaskStatus::PROCESSING, from: TaskStatus::APPROVED));
    }
}
