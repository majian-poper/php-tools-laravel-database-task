<?php

namespace PHPTools\LaravelDatabaseTask\Tests\Fixtures;

use Illuminate\Contracts\Support\Htmlable;
use PHPTools\LaravelDatabaseTask\Contracts\DatabaseTaskInterface;
use PHPTools\LaravelDatabaseTask\Contracts\InputInterface;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;
use PHPTools\LaravelDatabaseTask\Outputs\TextOutput;

class ExportUserTask implements DatabaseTaskInterface
{
    public static function getSupportInputs(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'User Preview Task';
    }

    public function preview(InputInterface ...$inputs): Htmlable
    {
        return new \Illuminate\Support\HtmlString('Preview HTML');
    }

    public function run(InputInterface ...$inputs): OutputInterface | \Generator
    {
        return new TextOutput('User ID: 1, Name: Alice');
    }
}
