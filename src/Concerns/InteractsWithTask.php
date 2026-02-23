<?php

namespace PHPTools\LaravelDatabaseTask\Concerns;

use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;

trait InteractsWithTask
{
    use Conditionable;
    use EvaluatesClosures;

    public function getTitle(): string
    {
        $taskName = Str::of(static::class)->afterLast('\\')->snake();

        return __("database-task::tasks.title.{$taskName}");
    }
}
