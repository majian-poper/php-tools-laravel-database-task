<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableInput;
use PHPTools\LaravelDatabaseTask\Contracts\InputInterface;
use PHPTools\LaravelDatabaseTask\Facades\DatabaseTaskFacade;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $database_task_id
 * @property string $input_class
 * @property string $input_value
 * @property bool $is_file
 * @property bool $is_excluded
 * @property int $batch_order
 */
class DatabaseTaskInput extends Model implements HasMedia
{
    use Concerns\InteractsWithMedia;

    protected $casts = [
        'database_task_id' => 'int',
        'input_class' => 'string',
        'input_value' => 'string',
        'is_file' => 'bool',
        'is_excluded' => 'bool',
        'batch_order' => 'int',
    ];

    protected $fillable = [
        'database_task_id',
        'input_class',
        'input_value',
        'is_file',
        'is_excluded',
        'batch_order',
    ];

    protected ?InputInterface $inputInstance = null;

    protected ?\SplFileObject $cacheFile = null;

    // --- DatabaseTask ---

    public static function fromArray(array $data, int $batchOrder = 0, ?DatabaseTask $databaseTask = null): ?static
    {
        if (! isset($data['input_class'], $data['input_value'])) {
            return null;
        }

        $inputClass = $data['input_class'];

        if (! \class_exists($inputClass) || ! \is_subclass_of($inputClass, InputInterface::class)) {
            return null;
        }

        $model = new static(
            [
                'input_class' => $inputClass,
                'input_value' => DatabaseTaskFacade::valueToString($data['input_value']),
                'is_file' => false, // TODO: 支持 file 格式
                'is_excluded' => \boolval($data['is_excluded'] ?? false),
                'batch_order' => $batchOrder,
            ]
        );

        if (filled($databaseTask)) {
            $model->task()->associate($databaseTask);
        }

        return $model;
    }

    public static function fromInput(InputInterface $input, ?DatabaseTask $databaseTask = null): static
    {
        $model = new static(
            [
                'input_class' => \get_class($input),
                'input_value' => DatabaseTaskFacade::valueToString($input->getValue()),
                'is_file' => false, // TODO: 支持 file 格式
                'is_excluded' => $input->isExcluded(),
                'batch_order' => $input instanceof BatchableInput ? $input->getBatchOrder() : 0,
            ]
        );

        $model->inputInstance = $input;

        if (filled($databaseTask)) {
            $model->task()->associate($databaseTask);
        }

        return $model;
    }

    /**
     * @return InputInterface | \PHPTools\LaravelDatabaseTask\Concerns\InteractsWithInput
     */
    public function toInput(): InputInterface
    {
        if (isset($this->inputInstance)) {
            return $this->inputInstance;
        }

        /** @var InputInterface | \PHPTools\LaravelDatabaseTask\Concerns\InteractsWithInput */
        $input = app($this->input_class);

        $input->excluded($this->is_excluded)
            ->when(
                $this->is_file,
                fn($input) => $input->asFile()->value(fn(): \SplFileObject => new \SplFileObject($this->file->getFilePath())),
                fn($input) => $input->value(DatabaseTaskFacade::stringToValue($this->input_value, $input->getType()))
            );

        return $this->inputInstance = $input;
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
