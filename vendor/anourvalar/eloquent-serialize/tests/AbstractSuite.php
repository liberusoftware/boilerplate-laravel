<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\File;
use AnourValar\EloquentSerialize\Tests\Models\User;
use AnourValar\EloquentSerialize\Tests\Models\UserPhoneNote;
use Illuminate\Database\Schema\Blueprint;

abstract class AbstractSuite extends \Orchestra\Testbench\TestCase
{
    /**
     * @var \AnourValar\EloquentSerialize\Service
     */
    protected $service;

    /**
     * Init
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/factories');
        $this->setUpDatabase($this->app);
        $this->setUpSeeder();

        \DB::enableQueryLog();

        $this->service = \App::make(\AnourValar\EloquentSerialize\Service::class);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function setUpDatabase(\Illuminate\Foundation\Application $app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('sort');
            $table->jsonb('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $app['db']->connection()->getSchemaBuilder()->create('user_phones', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('phone');
            $table->boolean('is_primary');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('user_phone_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_phone_id');
            $table->string('note');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    protected function setUpSeeder()
    {
        factory(UserPhoneNote::class)->times(80)->create();
        factory(File::class)->times(40)->create();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param bool $execute
     * @return void
     */
    protected function compare(\Illuminate\Database\Eloquent\Builder $builder, bool $execute = true): void
    {
        $referenceBuilder = clone $builder;
        $referenceSerialize = $this->service->serialize($builder);

        for ($i = 1; $i <= 3; $i++) {
            $builder = $this->service->serialize($builder);
            $this->assertSame($referenceSerialize, $builder, "#$i");

            $builder = json_encode($builder);
            $builder = json_decode($builder, true);
            $builder = $this->service->unserialize($builder);
            $this->assertSame($this->getScheme($referenceBuilder, $execute), $this->getScheme($builder, $execute), "#$i");
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param bool $execute
     * @return string
     */
    private function getScheme(\Illuminate\Database\Eloquent\Builder $builder, bool $execute): string
    {
        \DB::flushQueryLog();
        if ($execute) {
            $result = $builder->get();
        } else {
            $result = [];
        }
        $logs = \DB::getQueryLog();

        foreach ($logs as &$log) {
            unset($log['time']);
        }
        unset($log);

        return json_encode(['query' => $logs, 'result' => $result], JSON_PRETTY_PRINT);
    }
}
