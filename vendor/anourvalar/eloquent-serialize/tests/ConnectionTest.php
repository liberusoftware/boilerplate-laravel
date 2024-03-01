<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;

class ConnectionTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testDefault()
    {
        $package = $this->service->serialize(User::where('id', '!=', 1));
        $package = json_encode($package);

        $package = json_decode($package, true);
        $builder = $this->service->unserialize($package);

        $this->assertEquals('testing', $builder->getQuery()->getConnection()->getName());
        $this->assertEquals('testing', $builder->first()->getConnectionName());
    }

    /**
     * @return void
     */
    public function testSimple()
    {
        config(['database.connections.foo' => config('database.connections.testing')]);

        $package = $this->service->serialize((new User())->setConnection('foo')->where('id', '!=', 1));
        $package = json_encode($package);

        $package = json_decode($package, true);
        $builder = $this->service->unserialize($package);

        $this->assertEquals('foo', $builder->getQuery()->getConnection()->getName());
        $this->assertEquals('foo', $builder->getModel()->getConnectionName());
    }
}
