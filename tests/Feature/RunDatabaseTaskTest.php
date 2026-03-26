<?php

use PHPTools\LaravelDatabaseTask\Enums\TaskStatus;
use PHPTools\LaravelDatabaseTask\Jobs\RunDatabaseTask;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;
use PHPTools\LaravelDatabaseTask\Tests\Fixtures\ExportTeacherAttendanceTask;
use PHPTools\LaravelDatabaseTask\Tests\Fixtures\ExportUserTask;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

test('normal task scenario', function () {
    $task = DatabaseTask::create([
        'user_type' => 'App\Models\User',
        'user_id' => 1,
        'task_class' => ExportUserTask::class,
        'title' => 'User Preview',
        'risk' => 'low',
        'description' => 'description',
        'status' => TaskStatus::PENDING,
    ]);

    new RunDatabaseTask($task)->handle();

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::PROCESSED);
    expect($task->outputs)->toHaveCount(1);
    expect($task->outputs->first()->output_value)->toBe('User ID: 1, Name: Alice');
    expect($task->outputs->first()->is_file)->toBeFalse();
});

test('iterator task scenario', function () {
    $task = DatabaseTask::create([
        'user_type' => 'App\Models\User',
        'user_id' => 1,
        'task_class' => ExportTeacherAttendanceTask::class,
        'title' => 'Attendance Export',
        'risk' => 'medium',
        'description' => 'description',
        'status' => TaskStatus::PENDING,
    ]);

    new RunDatabaseTask($task)->handle();

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::PROCESSED);
    expect($task->outputs)->toHaveCount(1);

    $output = $task->outputs->first();
    expect($output->is_file)->toBeTrue();

    // Check if media is attached
    $media = Media::where('model_type', get_class($output))
        ->where('model_id', $output->id)
        ->first();

    expect($media)->not->toBeNull();
    expect($media->file_name)->toEndWith('.csv');

    // Verify CSV content
    $filePath = $media->getPath();
    $content = file_get_contents($filePath);
    expect($content)->toContain('Date,Teacher,Status');
    expect($content)->toContain('2023-10-01,"John Doe",Present');
    expect($content)->toContain('2023-10-01,"Jane Smith",Absent');
});
