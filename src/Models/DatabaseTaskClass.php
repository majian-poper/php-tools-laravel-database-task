<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Illuminate\Database\Eloquent\Model;
use PHPTools\LaravelDatabaseTask\Contracts\DatabaseTaskInterface;
use Sushi\Sushi;

/**
 * @property string $md5
 * @property string $title
 * @property string $task_class
 */
class DatabaseTaskClass extends Model
{
    use Sushi;

    public function getRows()
    {
        return collect(config('database-task.tasks.classes', []))
            ->map(
                static function (string $taskClass): ?array {
                    if (! \class_exists($taskClass)) {
                        return null;
                    }

                    if (! \is_subclass_of($taskClass, DatabaseTaskInterface::class)) {
                        return null;
                    }

                    return [
                        'md5' => \md5($taskClass),
                        'title' => (new $taskClass)->getTitle(),
                        'task_class' => $taskClass,
                    ];
                }
            )
            ->filter()
            ->all();
    }
}
