<?php

namespace PHPTools\LaravelDatabaseTask\Facades;

use Illuminate\Support\Facades\Facade;
use PHPTools\LaravelDatabaseTask\DatabaseTaskManager;

/**
 * @mixin \PHPTools\LaravelDatabaseTask\DatabaseTaskManager
 * @see \PHPTools\LaravelDatabaseTask\DatabaseTaskManager
 *
 * @method static string resolveModelClass(string $modelClass)
 * @method static \Illuminate\Database\Eloquent\Model resolveModel(string $modelClass)
 * @method static string valueToString(mixed $value)
 * @method static mixed stringToValue(string $string, \PHPTools\LaravelDatabaseTask\Enums\InputType $type)
 */
class DatabaseTaskFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DatabaseTaskManager::class;
    }
}
