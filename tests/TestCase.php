<?php

namespace HotwiredLaravel\TurboLaravel\Tests;

use HotwiredLaravel\TurboLaravel\TurboServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            TurboServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('turbo-laravel.models_namespace', [
            __NAMESPACE__.'\\Stubs\\Models\\',
        ]);
    }

    private function setUpDatabase(Application $app): void
    {
        $app['db']->connection()->getSchemaBuilder()->create('broadcast_test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->foreignId('parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $app['db']->connection()->getSchemaBuilder()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->text('body');
            $table->timestamps();
        });
    }
}
