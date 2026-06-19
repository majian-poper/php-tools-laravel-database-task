<?php

namespace PHPTools\LaravelDatabaseTask\Facades;

use Illuminate\Support\Facades\Facade;
use PHPTools\LaravelDatabaseTask\DatabaseTaskManager;

/**
 * @mixin \PHPTools\LaravelDatabaseTask\DatabaseTaskManager
 * @see \PHPTools\LaravelDatabaseTask\DatabaseTaskManager
 *
 * @template InputInterface of \PHPTools\LaravelDatabaseTask\Contracts\InputInterface
 * @template OutputInterface of \PHPTools\LaravelDatabaseTask\Contracts\OutputInterface
 * @template TaskModel of \PHPTools\LaravelDatabaseTask\Models\DatabaseTask
 * @template InputModel of \PHPTools\LaravelDatabaseTask\Models\DatabaseTaskInput
 * @template OutputModel of \PHPTools\LaravelDatabaseTask\Models\DatabaseTaskOutput
 *
 * @method static string resolveModelClass(string $modelClass)
 * @method static TaskModel resolveModel(string $modelClass)
 * @method static InputModel fromInput(InputInterface $input, ?TaskModel $databaseTask = null)
 * @method static InputModel fromInputArray(array $input, int $batchOrder = 0, ?TaskModel $databaseTask = null)
 * @method static OutputModel fromOutput(OutputInterface $output, ?TaskModel $databaseTask = null)
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
