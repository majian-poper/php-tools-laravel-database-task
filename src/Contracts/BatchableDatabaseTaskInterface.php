<?php

namespace PHPTools\LaravelDatabaseTask\Contracts;

interface BatchableDatabaseTaskInterface extends DatabaseTaskInterface
{
    /**
     * 定义如何分批。返回一个数组，每个元素将触发一个 Job 的执行
     *
     * @param InputInterface ...$inputs
     * @return array<int, mixed>
     */
    public function getBatches(InputInterface ...$inputs): array;

    /**
     * 定义如何合并多个子批次的输出
     *
     * @param OutputInterface[] $outputs
     * @return OutputInterface
     */
    public function mergeOutputs(array $outputs): OutputInterface;
}
