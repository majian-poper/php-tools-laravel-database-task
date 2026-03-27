<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;

/**
 * @property int $database_task_id
 * @property int $batch_index
 * @property array|null $context
 * @property TaskStatus $status
 * @property int|null $database_task_output_id
 *
 * @property DatabaseTask $databaseTask
 * @property DatabaseTaskOutput|null $output
 */
class DatabaseTaskBatch extends Model
{
    protected $fillable = [
        'database_task_id',
        'batch_index',
        'context',
        'status',
        'database_task_output_id',
    ];

    protected $casts = [
        'context' => 'array',
        'status' => TaskStatus::class,
    ];

    public function markAs(TaskStatus $status): static
    {
        return $this->setAttribute('status', $status);
    }

    public function databaseTask(): BelongsTo
    {
        return $this->belongsTo(config('database-task.implementations.database_task', DatabaseTask::class));
    }

    public function output(): BelongsTo
    {
        return $this->belongsTo(
            config('database-task.implementations.database_task_output', DatabaseTaskOutput::class),
            'database_task_output_id'
        );
    }
}
