<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPTools\LaravelDatabaseTask\Contracts\InputInterface;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $database_task_id
 * @property string $input_class
 * @property string $input_value
 * @property bool $is_file
 * @property bool $is_excluded
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
    ];

    protected $fillable = [
        'database_task_id',
        'input_class',
        'input_value',
        'is_file',
        'is_excluded',
    ];

    protected ?InputInterface $inputInstance = null;

    // --- DatabaseTask ---

    /**
     * @return InputInterface | \PHPTools\LaravelDatabaseTask\Concerns\InteractsWithInput
     */
    public function toInput(): InputInterface
    {
        if (! isset($this->inputInstance)) {
            /** @var InputInterface | \PHPTools\LaravelDatabaseTask\Concerns\InteractsWithInput */
            $this->inputInstance = app($this->input_class);

            $this->inputInstance
                ->when(
                    $this->is_file,
                    fn($input) => $input->asFile(),
                    fn($input) => $input->value($this->input_value)
                )
                ->excluded($this->is_excluded);
        }

        return $this->inputInstance;
    }

    // --- Relationships ---

    public function task(): BelongsTo
    {
        return $this->belongsTo(
            config('database-task.implementations.database_task', DatabaseTask::class),
            'database_task_id'
        );
    }
}
