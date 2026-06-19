<?php

namespace PHPTools\LaravelDatabaseTask\Concerns;

use Illuminate\Support\Collection;
use PHPTools\LaravelDatabaseTask\Contracts\BatchableInput;
use PHPTools\LaravelDatabaseTask\Contracts\InputInterface;

/**
 * @mixin \PHPTools\LaravelDatabaseTask\Contracts\TaskInterface
 * @see \PHPTools\LaravelDatabaseTask\Contracts\TaskInterface
 *
 * @method static array<InputInterface> getSupportInputs()
 */
trait InteractsWithBatchableTask
{
    use InteractsWithTask {
        filterInputs as protected baseFilterInputs;
    }

    public function filterInputs(InputInterface ...$inputs): Collection
    {
        $getBatchorder = static fn(InputInterface $input): int => $input instanceof BatchableInput
            ? $input->getBatchOrder()
            : 0;

        $inputs = collect($inputs)
            ->groupBy->getName()
            ->map(static fn(Collection $inputs): InputInterface => $inputs->sortByDesc($getBatchorder)->first());

        $supportInputs = collect(static::getSupportInputs())->keyBy->getName();

        $validInputs = collect();

        /** @var InputInterface|\PHPTools\LaravelDatabaseTask\Concerns\InteractsWithInput $input */
        foreach ($supportInputs as $name => $input) {
            if ($input->isRequired() && ! $inputs->has($name)) {
                throw new \InvalidArgumentException(__('validation.required', ['attribute' => $input->getLabel()]));
            }

            $validInputs[$name] = $inputs->get($name);
        }

        return $validInputs;
    }

    public function getBatchableInputs(InputInterface ...$inputs): iterable
    {
        $filteredInputs = $this->filterInputs(...$inputs);

        return $this->handleGetBatchableInputs($filteredInputs);
    }

    abstract protected function handleGetBatchableInputs(Collection $filteredInputs): iterable;
}
