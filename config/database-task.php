<?php

return [

    'implementations' => [
        'database_task' => \PHPTools\LaravelDatabaseTask\Models\DatabaseTask::class,
        'database_task_input' => \PHPTools\LaravelDatabaseTask\Models\DatabaseTaskInput::class,
        'database_task_output' => \PHPTools\LaravelDatabaseTask\Models\DatabaseTaskOutput::class,
        'database_task_file' => \PHPTools\LaravelDatabaseTask\Models\DatabaseTaskFile::class,
        'database_task_batch' => \PHPTools\LaravelDatabaseTask\Models\DatabaseTaskBatch::class,
    ],

    'tasks' => [
        'classes' => [],
    ],

];
