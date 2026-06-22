<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableOutput;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use PHPTools\LaravelDatabaseTask\Outputs\FileOutput;
use Spatie\MediaLibrary\HasMedia;

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

    protected ?OutputInterface $outputInstance = null;

    public static function fromOutput(OutputInterface $output, ?DatabaseTask $databaseTask = null): static
    {
        $value = $output->getValue();
        $isFile = $value instanceof \SplFileObject && $value->isReadable();

        $model = new static(
            [
                'output_class' => \get_class($output),
                'output_value' => $isFile ? '' : DatabaseTaskFacade::valueToString($value),
                'is_file' => $isFile,
                'expires_at' => $output->getExpiresAt(),
                'batch_order' => $output instanceof BatchableOutput ? $output->getBatchOrder() : 0,
            ]
        );

        if (filled($databaseTask)) {
            $model->task()->associate($databaseTask);
        }

        $isFile && $model->saved(static fn() => $model->addMedia($value->getRealPath())->toMediaCollection());

        $model->outputInstance = $output;

        return $model;
    }

    /**
     * @return OutputInterface | \PHPTools\LaravelDatabaseTask\Concerns\InteractsWithOutput
     */
    public function toOutput(): OutputInterface
    {
        if (isset($this->outputInstance)) {
            return $this->outputInstance;
        }

        // TODO try-catch

        $isFile = $this->is_file && $this->file && \is_readable($filename = $this->file->getFilePath());

        if ($isFile && \is_a($this->output_class, FileOutput::class, true)) {
            /** @var FileOutput $output */
            $output = app($this->output_class, \compact('filename'));
        } else {
            /** @var OutputInterface | \PHPTools\LaravelDatabaseTask\Concerns\InteractsWithOutput $output */
            $output = app($this->output_class);

            $output->value($this->output_value);
        }

        if ($output instanceof BatchableOutput && \method_exists($output, 'batchOrder')) {
            $output->batchOrder($this->batch_order);
        }

        return $this->outputInstance = $output;
    }

    // --- Expiration Management ---

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired();
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
