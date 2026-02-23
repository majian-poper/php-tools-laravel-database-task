<?php

namespace PHPTools\LaravelDatabaseTask\Enums\Concerns;

trait HasFilamentOptions
{
    public static function getFilamentOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn(self $type): array => [$type->value => $type->getLabel()])
            ->toArray();
    }
}
