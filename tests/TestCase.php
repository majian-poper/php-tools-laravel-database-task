<?php

namespace PHPTools\LaravelDatabaseTask\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

class TestCase extends Orchestra
{
    protected function defineDatabaseMigrations()
    {
        $migration = include __DIR__ . '/../database/migrations/create_database_tasks_table.php.stub';
        $migration->up();

        $this->setupMediaTable();
    }

    protected function getPackageProviders($app)
    {
        return [
            MediaLibraryServiceProvider::class,
        ];
    }

    protected function setupMediaTable()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->timestamps();
        });
    }
}
