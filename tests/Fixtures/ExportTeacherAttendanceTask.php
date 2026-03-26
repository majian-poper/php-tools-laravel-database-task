<?php

namespace PHPTools\LaravelDatabaseTask\Tests\Fixtures;

use Illuminate\Contracts\Support\Htmlable;
use PHPTools\LaravelDatabaseTask\Contracts\DatabaseTaskInterface;
use PHPTools\LaravelDatabaseTask\Contracts\InputInterface;
use PHPTools\LaravelDatabaseTask\Contracts\OutputInterface;

class ExportTeacherAttendanceTask implements DatabaseTaskInterface
{
    public static function getSupportInputs(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Teacher Attendance Export';
    }

    public function preview(InputInterface ...$inputs): Htmlable
    {
        return new \Illuminate\Support\HtmlString('Preview HTML');
    }

    public function run(InputInterface ...$inputs): OutputInterface | \Generator
    {
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_attendance_' . uniqid() . '.csv';

        $output = new FileOutput($tempFile, 'w+');

        yield $output;

        yield ['Date', 'Teacher', 'Status'];
        yield ['2023-10-01', 'John Doe', 'Present'];
        yield ['2023-10-01', 'Jane Smith', 'Absent'];
    }
}
