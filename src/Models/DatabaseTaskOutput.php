<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableOutput;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Events;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use Spatie\MediaLibrary\HasMedia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property int $database_task_id
 * @property string $output_class
 * @property string $output_value
 * @property bool $is_file
 * @property int $batch_order
 * @property \Carbon\CarbonImmutable | null $expires_at
 *
 * @property-read DatabaseTask $task
 */
class DatabaseTaskOutput extends Model implements HasMedia
{
    use Concerns\InteractsWithMedia;

    protected $casts = [
        'database_task_id' => 'int',
        'output_class' => 'string',
        'output_value' => 'string',
        'is_file' => 'bool',
        'batch_order' => 'int',
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'database_task_id',
        'output_class',
        'output_value',
        'is_file',
        'batch_order',
        'expires_at',
    ];

    public static function fromOutput(OutputInterface $output, ?DatabaseTask $databaseTask = null): static
    {
        $value = $output->getValue();
        $isFile = $value instanceof \SplFileObject;

        $model = new static(
            [
                'output_class' => \get_class($output),
                'output_value' => DatabaseTaskFacade::valueToString($output->getValue()),
                'is_file' => $isFile,
                'expires_at' => $output->getExpiresAt(),
                'batch_order' => $output instanceof BatchableOutput ? $output->getBatchOrder() : 0,
            ]
        );

        if (filled($databaseTask)) {
            $model->task()->associate($databaseTask);
        }

        return $model;
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    public function toDownloadAction(): Action
    {
        return Action::make('download')
            ->label(__('database-task::model.database_task_output.actions.download'))
            ->visible(fn(): bool => $this->is_file && $this->isValid() && isset($this->file))
            ->before(fn() => Events\TaskOutputDownloading::dispatch($this, Auth::user()))
            ->action(fn(): StreamedResponse => $this->file->toResponse(request()));
    }

    // --- Relationships ---

    public function task(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseTaskFacade::resolveModelClass(DatabaseTask::class),
            'database_task_id'
        );
    }
}
