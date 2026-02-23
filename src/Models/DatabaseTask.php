<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use PHPTools\LaravelDatabaseTask\Contracts\DatabaseTaskInterface;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Enums;
use PHPTools\LaravelDatabaseTask\Events;

/**
 * @property string $user_type
 * @property int $user_id
 * @property string $task_class
 * @property string $title
 * @property string $description
 * @property Enums\TaskRisk $risk
 * @property Enums\TaskStatus $status
 * @property \Carbon\CarbonImmutable | null $schedules_at
 *
 * @property \Illuminate\Database\Eloquent\Model $user
 *
 * @property \Illuminate\Database\Eloquent\Collection<DatabaseTaskInput> $inputs
 * @property \Illuminate\Database\Eloquent\Collection<DatabaseTaskOutput> $outputs
 */
class DatabaseTask extends Model
{
    use SoftDeletes;

    protected $casts = [
        'user_type' => 'string',
        'user_id' => 'integer',
        'task_class' => 'string',
        'title' => 'string',
        'description' => 'string',
        'risk' => Enums\TaskRisk::class,
        'status' => Enums\TaskStatus::class,
        'schedules_at' => 'immutable_datetime',
    ];

    protected $fillable = [
        'user_type',
        'user_id',
        'task_class',
        'title',
        'description',
        'status',
        'schedules_at',
    ];

    protected ?DatabaseTaskInterface $taskInstance = null;

    // --- DatabaseTask ---

    public function toTask(): DatabaseTaskInterface
    {
        return $this->taskInstance ??= app($this->task_class);
    }

    public function run(): OutputInterface
    {
        return $this->toTask()->run(...$this->inputs->map->toInput()->all());
    }

    public function markAs(Enums\TaskStatus $status): static
    {
        return $this->setAttribute('status', $status);
    }

    public function requestable(): bool
    {
        return $this->status === Enums\TaskStatus::UNAPPLIED;
    }

    public function request(): bool
    {
        $result = $this->markAs(Enums\TaskStatus::PENDING)->save();

        if ($result) {
            Events\DatabaseTaskRequested::dispatch($this, Auth::user());
        }

        return $result;
    }

    // public function isApproved(): bool
    // {
    //     return $this->status === Enums\TaskStatus::APPROVED;
    // }

    // public function shouldBeScheduled(): bool
    // {
    //     return $this->schedules_at?->isFuture() ?? false;
    // }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->morphTo('user');
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(
            config('database-task.implementations.database_task_input', DatabaseTaskInput::class),
            'database_task_id'
        );
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(
            config('database-task.implementations.database_task_output', DatabaseTaskOutput::class),
            'database_task_id'
        );
    }
}
