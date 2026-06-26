<?php

namespace PHPTools\LaravelDatabaseTask\Concerns;

use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use PHPTools\LaravelDatabaseTask\Contracts;

/**
 * @method static array<Contracts\InputInterface> getSupportInputs()
 */
trait InteractsWithTask
{
    use Conditionable;
    use EvaluatesClosures;

    public function getTitle(): string
    {
        $taskName = Str::of(static::class)->afterLast('\\')->snake();

        return __("database-task::tasks.title.{$taskName}");
    }

    public function preview(Contracts\InputInterface ...$inputs): Htmlable
    {
        $filteredInputs = $this->filterInputs(...$inputs);

        return $this->handlePreview($filteredInputs);
    }

    public function run(Contracts\InputInterface ...$inputs): Contracts\OutputInterface
    {
        $filteredInputs = $this->filterInputs(...$inputs);

        return $this->handleRun($filteredInputs);
    }

    public function showOutputs(): bool
    {
        return true;
    }

    protected function filterInputs(Contracts\InputInterface ...$inputs): Collection
    {
        $supportInputs = collect(static::getSupportInputs())->keyBy->getName();

        $inputs = collect($inputs)->keyBy->getName();

        $filteredInputs = collect();

        foreach ($supportInputs as $name => $_) {
            $filteredInputs[$name] = $inputs->pull($name);
        }

        return $filteredInputs;
    }

    abstract protected function handlePreview(Collection $inputs): Htmlable;

    abstract protected function handleRun(Collection $inputs): Contracts\OutputInterface;
}
