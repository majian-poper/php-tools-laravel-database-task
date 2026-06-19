<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use PHPTools\LaravelDatabaseTask\Contracts;
use PHPTools\LaravelDatabaseTask\Enums;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use PHPTools\LaravelDatabaseTask\Outputs\TextOutput;

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
 * @property-read string $job_name
 * @property-read \Illuminate\Database\Eloquent\Model $user
 * @property-read \Illuminate\Database\Eloquent\Collection<DatabaseTaskInput> $inputs
 * @property-read \Illuminate\Database\Eloquent\Collection<DatabaseTaskOutput> $outputs
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
        'risk',
        'status',
        'schedules_at',
    ];

    protected ?Contracts\TaskInterface $taskInstance = null;

    // --- DatabaseTask ---

    public function toTask(): ?Contracts\TaskInterface
    {
        if (isset($this->taskInstance)) {
            return $this->taskInstance;
        }

        try {
            $task = app($this->task_class);

            if (! $task instanceof Contracts\TaskInterface) {
                throw new \RuntimeException(
                    \sprintf(
                        'Task class %s must implement %s interface.',
                        $this->task_class,
                        Contracts\TaskInterface::class
                    )
                );
            }
        } catch (\Throwable $e) {
            $task = null;
        }

        return $this->taskInstance = $task;
    }

    /**
     * Get all non-batchable inputs for this task.
     *
     * @return array<Contracts\InputInterface>
     */
    public function getInputs(): array
    {
        return $this->inputs()->get()->map->toInput()->whereInstanceOf(Contracts\InputInterface::class)->all();
    }

    /**
     * Get all non-batchable outputs for this task.
     */
    public function getOutputs(): array
    {
        return $this->outputs()->get()->map->toOutput()->whereInstanceOf(Contracts\OutputInterface::class)->all();
    }

    /**
     * Get all inputs for the specified batch order, including all non-batchable inputs.
     *
     * @return array<Contracts\InputInterface | Contracts\BatchableInput>
     */
    public function getInputsForBatch(int $batchOrder = 0): array
    {
        return $this->inputs($batchOrder)->get()->map->toInput()->whereInstanceOf(Contracts\InputInterface::class)->all();
    }

    /**
     * Get all batchable outputs
     *
     * @return array<Contracts\BatchableOutput>
     */
    public function getBatchableOutputs(): array
    {
        return $this->batch_outputs()->get()->map->toOutput()->whereInstanceOf(Contracts\BatchableOutput::class)->all();
    }

    // --- Status Management ---

    public function markAs(Enums\TaskStatus $status): static
    {
        return $this->setAttribute('status', $status);
    }

    public function updateStatus(Enums\TaskStatus $to, ?Enums\TaskStatus $from = null): bool
    {
        try {
            return $this->getConnection()->transaction(
                fn(): bool => $this->newQuery()
                    ->whereKey($this->getKey())
                    ->when(isset($from), static fn($query) => $query->where('status', $from))
                    ->lockForUpdate()
                    ->update(['status' => $to]) > 0,
                3
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function updateStatusFailed(string $reason): bool
    {
        $databaseOutput = DatabaseTaskFacade::resolveModel(DatabaseTaskOutput::class)
            ->fromOutput(new TextOutput($reason), $this);

        try {
            $this->getConnection()->transaction(
                function () use ($databaseOutput) {
                    $this->markAs(Enums\TaskStatus::FAILED)->save();

                    $databaseOutput->save();
                }
            );
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    // --- Task actions ---

    public function previewable(): bool
    {
        return \in_array($this->status, [Enums\TaskStatus::UNAPPLIED, Enums\TaskStatus::PENDING])
            || $this->outputs->isEmpty();
    }

    public function preview(): Htmlable
    {
        return $this->toTask()->preview(...$this->getInputs());
    }

    public function requestable(): bool
    {
        return $this->status === Enums\TaskStatus::UNAPPLIED;
    }

    public function request(): bool
    {
        $result = $this->markAs(Enums\TaskStatus::PENDING)->save();

        if ($result) {
            Events\TaskRequested::dispatch($this, Auth::user());
        }

        return $result;
    }

    // --- Scheduling ---

    public function shouldBeScheduled(): bool
    {
        return $this->schedules_at?->isFuture() ?? false;
    }

    // --- Accessors ---

    public function jobName(): Attribute
    {
        return Attribute::make(
            get: fn(): string => \sprintf('%s#%d', class_basename($this->task_class), $this->getKey()),
        );
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->morphTo('user');
    }

    public function inputs(int $batchOrder = 0): HasMany
    {
        return $this
            ->hasMany(DatabaseTaskFacade::resolveModelClass(DatabaseTaskInput::class), 'database_task_id')
            ->where(
                static fn(Builder $query) => $query->where('batch_order', 0)
                    ->when($batchOrder > 0)->orWhere('batch_order', $batchOrder)
            );
    }

    public function batch_inputs(): HasMany
    {
        return $this
            ->hasMany(DatabaseTaskFacade::resolveModelClass(DatabaseTaskInput::class), 'database_task_id')
            ->where('batch_order', '>', 0);
    }

    public function outputs(): HasMany
    {
        return $this
            ->hasMany(DatabaseTaskFacade::resolveModelClass(DatabaseTaskOutput::class), 'database_task_id')
            ->where('batch_order', 0);
    }

    public function batch_outputs(): HasMany
    {
        return $this
            ->hasMany(DatabaseTaskFacade::resolveModelClass(DatabaseTaskOutput::class), 'database_task_id')
            ->where('batch_order', '>', 0);
    }
}
