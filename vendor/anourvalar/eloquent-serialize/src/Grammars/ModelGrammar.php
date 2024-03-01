<?php

namespace AnourValar\EloquentSerialize\Grammars;

trait ModelGrammar
{
    /**
     * Pack
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return \AnourValar\EloquentSerialize\Package
     */
    protected function pack(\Illuminate\Database\Eloquent\Builder $builder): \AnourValar\EloquentSerialize\Package
    {
        return new \AnourValar\EloquentSerialize\Package([
            'model' => get_class($builder->getModel()),
            'connection' => $builder->getModel()->getConnectionName(),
            'eloquent' => $this->packEloquentBuilder($builder),
            'query' => $this->packQueryBuilder($builder->getQuery()),
        ]);
    }

    /**
     * Unpack
     *
     * @param \AnourValar\EloquentSerialize\Package $package
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function unpack(\AnourValar\EloquentSerialize\Package $package): \Illuminate\Database\Eloquent\Builder
    {
        $builder = $package->get('model');
        $builder = $builder::on($package->get('connection'));

        $this->unpackEloquentBuilder($package->get('eloquent'), $builder);
        $this->unpackQueryBuilder($package->get('query'), $builder->getQuery());

        return $builder;
    }
}
