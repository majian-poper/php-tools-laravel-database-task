<?php

namespace PHPTools\LaravelDatabaseTask\Resources\DatabaseTasks\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PHPTools\LaravelDatabaseTask\Contracts\DatabaseTaskInterface;
use PHPTools\LaravelDatabaseTask\Contracts\InputInterface;
use PHPTools\LaravelDatabaseTask\DatabaseTaskPlugin;
use PHPTools\LaravelDatabaseTask\Enums\TaskRisk;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTaskClass;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTaskInput;

class CreateDatabaseTask extends CreateRecord
{
    public static function getResource(): string
    {
        return DatabaseTaskPlugin::getResourceClass();
    }

    public function getTitle(): string | Htmlable
    {
        $taskClassComponent = collect($this->form->getComponents(withActions: false))->first(
            /** @var Forms\Components\Component | Schemas\Components\Component $component */
            static fn($component): bool => \method_exists($component, 'getName') && $component->getName() === 'task_class'
        );

        $state = $taskClassComponent?->getState();

        if (isset($state) && \is_subclass_of($state, DatabaseTaskInterface::class)) {
            return (new $state)->getTitle();
        }

        return parent::getTitle();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $taskClass = DatabaseTaskClass::query()
            ->where('md5', request()->route('task_class'))
            ->firstOrFail();

        $this->form->fill(
            [
                'task_class' => $taskClass->task_class,
                'title' => $taskClass->title,
                'description' => '',
                'risk' => TaskRisk::MEDIUM->value,
                'schedules_at' => null,
                'inputs' => collect($taskClass->task_class::getSupportInputs())
                    ->mapWithKeys(
                        static fn(InputInterface $input): array => [
                            $input->getName() => [
                                'input_class' => \get_class($input),
                                'input_value' => null,
                                'is_file' => false,
                                'is_excluded' => false,
                            ]
                        ]
                    )
                    ->all(),
            ]
        );

        $this->callHook('afterFill');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getPreviewFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function handleRecordCreation(array $data): DatabaseTask
    {
        $task = static::getResource()::getModel()::query()->make($data);

        $task->user()->associate(Auth::user())->save();

        $task->inputs()->createMany(
            collect(Arr::pull($data, 'inputs'))
                ->filter(static fn(array $input): bool => filled($input['input_value']))
                ->map(
                    static function (array $input): array {
                        if (\is_array($input['input_value'])) {
                            $input['input_value'] = \implode(',', $input['input_value']);
                        }

                        return $input;
                    }
                )
        );

        return $task;
    }

    protected function getPreviewFormAction(): Actions\Action
    {
        return Actions\Action::make('preview')
            ->label(__('database-task::model.database_task.actions.preview.label'))
            ->modalHidden(
                function (): bool {
                    try {
                        $this->form->validate();
                    } catch (ValidationException $e) {
                        $this->setErrorBag($e->validator->errors());

                        return true;
                    }

                    return false;
                }
            )
            ->modalContent(
                function (): Htmlable {
                    $data = $this->form->getState();

                    $inputs = collect(Arr::pull($data, 'inputs'))
                        ->filter(static fn(array $input): bool => filled($input['input_value']))
                        ->map(
                            static function (array $input): InputInterface {
                                if (\is_array($input['input_value'])) {
                                    $input['input_value'] = implode(',', $input['input_value']);
                                }

                                return config('database-task.implementations.database_task_input', DatabaseTaskInput::class)::make($input)->toInput();
                            }
                        )
                        ->all();

                    return app($data['task_class'])->preview(...$inputs);
                }
            )
            ->modalFooterActions([]);
    }
}
