<?php

namespace PHPTools\LaravelDatabaseTask\Contracts;

interface BatchableTaskInterface extends TaskInterface
{
    /**
     * @return iterable<BatchableInput>
     */
    public function getBatchableInputs(InputInterface ...$inputs): iterable;

    public function mergeBatchableOutputs(OutputInterface ...$outputs): OutputInterface;
}
