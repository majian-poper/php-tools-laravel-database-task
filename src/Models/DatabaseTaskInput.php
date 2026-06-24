<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPTools\LaravelDatabaseTask\Contracts;
use PHPTools\LaravelDatabaseTask\Enums;
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

    protected ?Contracts\InputInterface $inputInstance = null;

    // --- DatabaseTask ---

    public static function fromArray(array $data, int $batchOrder = 0, ?DatabaseTask $databaseTask = null): ?static
    {
        if (! isset($data['input_class'], $data['input_value'])) {
            return null;
        }

        $inputClass = $data['input_class'];
        $inputValue = $data['input_value'];

        if (blank($inputValue)) {
            return null;
        }

        if (! \class_exists($inputClass) || ! \is_subclass_of($inputClass, Contracts\InputInterface::class)) {
            return null;
        }

        $model = static::query()->make(
            [
                'input_class' => $inputClass,
                'input_value' => DatabaseTaskFacade::valueToString($inputValue),
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

    public static function fromInput(Contracts\InputInterface $input, ?DatabaseTask $databaseTask = null): static
    {
        $model = static::query()->make(
            [
                'input_class' => \get_class($input),
                'input_value' => DatabaseTaskFacade::valueToString($input->getValue()),
                'is_file' => false, // TODO: 支持 file 格式
                'is_excluded' => $input->isExcluded(),
                'batch_order' => $input instanceof Contracts\BatchableInput ? $input->getBatchOrder() : 0,
            ]
        );

        if (filled($databaseTask)) {
            $model->task()->associate($databaseTask);
        }

        $model->inputInstance = $input;

        return $model;
    }

    public function toInput(): Contracts\InputInterface
    {
        if (isset($this->inputInstance)) {
            return $this->inputInstance;
        }

        // TODO try-catch

        $isFile = $this->is_file && $this->file;

        /** @var Contracts\InputInterface $input */
        $input = app($this->input_class);

        if (\method_exists($input, 'excluded')) {
            $input->excluded($this->is_excluded);
        }

        if ($isFile && \method_exists($input, 'asFile')) {
            $input->asFile();
        }

        if (\method_exists($input, 'value')) {
            $input->value(
                match (true) {
                    $isFile => $this->file->toTempFileObject(...),
                    default => $this->stringToValue($this->input_value, $input->getType()),
                }
            );
        }

        if ($input instanceof Contracts\BatchableInput && \method_exists($input, 'batchOrder')) {
            $input->batchOrder($this->batch_order);
        }

        return $this->inputInstance = $input;
    }

    // --- Relationships ---

    public function task(): BelongsTo
    {
        return $this->belongsTo(DatabaseTaskFacade::resolveModelClass(DatabaseTask::class), 'database_task_id');
    }

    // --- Helpers ---

    /**
     * @return null | bool | int | string | \DateTime | iterable
     */
    protected function stringToValue(string $string, Enums\InputType $type): mixed
    {
        return match ($type) {
            Enums\InputType::QUERY => $string ?: null,
            Enums\InputType::NUMBER => \is_numeric($string) ? (int) $string : null,
            Enums\InputType::SELECT => \explode(',', $string),
            Enums\InputType::DATETIME => CarbonImmutable::parse($string),
            Enums\InputType::BOOLEAN => \in_array($string, ['1', 'true', 'yes'], true),
            default => throw new \InvalidArgumentException('Unsupported input type.'),
        };
    }
}
