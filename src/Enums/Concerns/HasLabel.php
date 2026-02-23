<?php

namespace PHPTools\LaravelDatabaseTask\Enums\Concerns;

use Illuminate\Support\Str;

trait HasLabel
{
    public function getLabel(): string
    {
        $enumName = Str::of(static::class)->afterLast('\\')->snake();

        return __("database-task::enums.{$enumName}.{$this->name}");
    }
}
