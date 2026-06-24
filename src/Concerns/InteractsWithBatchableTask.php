<?php

namespace PHPTools\LaravelDatabaseTask\Concerns;

use Illuminate\Support\Collection;
use PHPTools\LaravelDatabaseTask\Contracts;

/**
 * @method static array<Contracts\InputInterface> getSupportInputs()
 */
trait InteractsWithBatchableTask
{
    use InteractsWithTask {
        filterInputs as interactsWithTaskFilterInputs;
    }

    public function getBatchableInputs(Contracts\InputInterface ...$inputs): iterable
    {
        $filteredInputs = $this->filterInputs(...$inputs);

        return $this->handleGetBatchableInputs($filteredInputs);
    }

    public function mergeBatchableOutputs(Contracts\BatchableOutput ...$outputs): Contracts\OutputInterface
    {
        $filteredOutputs = collect($outputs)->sortBy(
            static fn(Contracts\BatchableOutput $output): int => $output->getBatchOrder()
        );

        return $this->handleMergeBatchableOutputs($filteredOutputs);
    }

    protected function filterInputs(Contracts\InputInterface ...$inputs): Collection
    {
        $supportInputs = collect(static::getSupportInputs())->keyBy->getName();

        $getBatchorder = static function (Contracts\InputInterface $input): int {
            return $input instanceof Contracts\BatchableInput ? $input->getBatchOrder() : 0;
        };

        $inputs = collect($inputs)
            ->groupBy->getName()
            ->map(static fn(Collection $inputs): Contracts\InputInterface => $inputs->sortByDesc($getBatchorder)->first());

        $filteredInputs = collect();

        /** @var Contracts\InputInterface $input */
        foreach ($supportInputs as $name => $_) {
            $filteredInputs[$name] = $inputs->pull($name);
        }

        return $filteredInputs;
    }

    abstract protected function handleGetBatchableInputs(Collection $filteredInputs): iterable;

    abstract protected function handleMergeBatchableOutputs(Collection $filteredOutputs): Contracts\OutputInterface;
}
