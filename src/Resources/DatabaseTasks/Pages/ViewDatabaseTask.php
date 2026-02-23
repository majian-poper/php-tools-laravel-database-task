<?php

namespace PHPTools\LaravelDatabaseTask\Resources\DatabaseTasks\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use PHPTools\LaravelDatabaseTask\DatabaseTaskPlugin;
use PHPTools\LaravelDatabaseTask\Models\DatabaseTask;

/**
 * @property-read \PHPTools\LaravelDatabaseTask\Models\DatabaseTask $record
 */
class ViewDatabaseTask extends ViewRecord
{
    public static function getResource(): string
    {
        return DatabaseTaskPlugin::getResourceClass();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->makeRequestAction(),
        ];
    }

    protected function makeRequestAction(): Actions\Action
    {
        return Actions\Action::make('request')
            ->label(__('database-task::model.database_task.actions.request.label'))
            ->requiresConfirmation()
            ->authorize('create', static::getResource()::getModel())
            ->visible(static fn(DatabaseTask $record): bool => $record->requestable())
            ->action(
                static function (DatabaseTask $record) {
                    Notification::make()
                        ->when(
                            $record->request(),
                            static fn(Notification $notification): Notification => $notification
                                ->success()
                                ->title(__('database-task::model.database_task.actions.request.notifications.requested')),
                            static fn(Notification $notification): Notification => $notification
                                ->danger()
                                ->title(__('database-task::model.database_task.actions.request.notifications.request_failed'))
                        )
                        ->send();
                }
            );
    }
}
