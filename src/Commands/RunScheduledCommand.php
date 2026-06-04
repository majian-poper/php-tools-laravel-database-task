<?php

namespace PHPTools\LaravelDatabaseTask\Commands;

use Illuminate\Console\Command;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Jobs\RunDatabaseTask;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

class RunScheduledCommand extends Command
{
    protected $signature = 'database-task:run-scheduled';

    protected $description = 'Dispatch scheduled database task jobs.';

    public function handle()
    {
        $taskModel = config('database-task.implementations.database_task', DatabaseTask::class);

        $taskModel::query()
            ->whereNotNull('schedules_at')
            ->where('schedules_at', '<=', now())
            ->where('status', TaskStatus::APPROVED)
            ->each($this->dispatchJob(...));
    }

    protected function dispatchJob(DatabaseTask $task): void
    {
        $task->markAs(TaskStatus::PROCESSING)->save();

        RunDatabaseTask::dispatch($task);
    }
}
