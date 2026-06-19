<?php

namespace PHPTools\LaravelDatabaseTask;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DatabaseTaskManager
{
    /**
     * The configuration repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    public function __construct(Application $app)
    {
        $this->config = $app->make('config');
    }

    /**
     * @template T of Model
     * @param class-string<T> $modelClass
     * @return class-string<T>
     */
    public function resolveModelClass(string $modelClass): string
    {
        if (! (\class_exists($modelClass) && \is_subclass_of($modelClass, Model::class, true))) {
            throw new \InvalidArgumentException("Model class {$modelClass} does not exist.");
        }

        $key = Str::snake(class_basename($modelClass));

        $configModelClass = $this->config->get("database-task.implementations.{$key}", $modelClass);

        if (\is_a($configModelClass, $modelClass, true)) {
            $modelClass = $configModelClass;
        }

        return $modelClass;
    }

    /**
     * @template T of Model
     * @param class-string<T> $modelClass
     * @return T
     */
    public function resolveModel(string $modelClass): Model
    {
        return new ($this->resolveModelClass($modelClass));
    }

    /**
     * @param null | bool | int | string | \DateTime | \SplFileObject | iterable $value
     */
    public function valueToString(mixed $value): string
    {
        return match (true) {
            \is_null($value) => '',
            $value instanceof \SplFileObject => '',
            \is_string($value), \is_numeric($value) => (string) $value,
            \is_bool($value) => $value ? '1' : '0',
            \is_iterable($value) => implode(',', $value),
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            default => throw new \InvalidArgumentException('Unsupported value type.'),
        };
    }

    /**
     * @return null | bool | int | string | \DateTime | iterable
     */
    public function stringToValue(string $string, Enums\InputType $type): mixed
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
