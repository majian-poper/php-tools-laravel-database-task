<?php

namespace PHPTools\LaravelDatabaseTask\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTaskFile;
use Spatie\MediaLibrary\InteractsWithMedia as SpatieInteractsWithMedia;

/**
 * @property-read DatabaseTaskFile | null $file
 */
trait InteractsWithMedia
{
    use SpatieInteractsWithMedia;

    public function getMediaModel(): string
    {
        return config('database-task.implementations.database_task_file', DatabaseTaskFile::class);
    }

    public function file(): MorphOne
    {
        return $this->media()->one();
    }
}
