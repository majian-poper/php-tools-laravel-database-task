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
        filterInputs as baseFilterInputs;
    }

    public function getBatchableInputs(Contracts\InputInterface ...$inputs): iterable
    {
        $filteredInputs = $this->filterInputs(...$inputs);

        return $this->handleGetBatchableInputs($filteredInputs);
    }

    public function mergeBatchableOutputs(Contracts\BatchableOutput ...$outputs): Contracts\OutputInterface
    {
        $filteredOutputs = collect($outputs)
            ->whereInstanceOf(Contracts\BatchableOutput::class)
            ->sortBy(static fn(Contracts\BatchableOutput $output): int => $output->getBatchOrder());

        return $this->handleMergeBatchableOutputs($filteredOutputs);
    }

    protected function filterInputs(Contracts\InputInterface ...$inputs): Collection
    {
        $getBatchorder = static fn(Contracts\InputInterface $input): int => $input instanceof Contracts\BatchableInput
            ? $input->getBatchOrder()
            : 0;

        $inputs = collect($inputs)
            ->groupBy->getName()
            ->map(static fn(Collection $inputs): Contracts\InputInterface => $inputs->sortByDesc($getBatchorder)->first());

        $supportInputs = collect(static::getSupportInputs())->keyBy->getName();

        $validInputs = collect();

        /** @var Contracts\InputInterface | \PHPTools\LaravelDatabaseTask\Concerns\InteractsWithInput $input */
        foreach ($supportInputs as $name => $input) {
            if ($input->isRequired() && ! $inputs->has($name)) {
                throw new \InvalidArgumentException(__('validation.required', ['attribute' => $input->getLabel()]));
            }

            $validInputs[$name] = $inputs->get($name);
        }

        return $validInputs;
    }

    abstract protected function handleGetBatchableInputs(Collection $filteredInputs): iterable;

    abstract protected function handleMergeBatchableOutputs(Collection $filteredOutputs): Contracts\OutputInterface;
}
