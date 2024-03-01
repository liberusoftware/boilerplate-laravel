<?php

namespace AnourValar\EloquentSerialize\Grammars;

trait EloquentBuilderGrammar
{
    /**
     * Serialize state for \Illuminate\Database\Eloquent\Builder
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return array
     */
    protected function packEloquentBuilder(\Illuminate\Database\Eloquent\Builder $builder): array
    {
        return [
            'with' => $this->getEagers($builder), // preloaded ("eager") relations
            'removed_scopes' => $builder->removedScopes(), // global scopes
            'casts' => $builder->getModel()->getCasts(),
        ];
    }

    /**
     * Unserialize state for \Illuminate\Database\Eloquent\Builder
     *
     * @param array $data
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    protected function unpackEloquentBuilder(array $data, \Illuminate\Database\Eloquent\Builder &$builder): void
    {
        // Preloaded ("eager") relations
        $this->setEagers($builder, $data['with']);

        // Global scopes
        if ($data['removed_scopes']) {
            $builder->withoutGlobalScopes($data['removed_scopes']);
        }

        // Casts
        if (method_exists($builder->getModel(), 'mergeCasts')) { // old versions support
            $builder->getModel()->mergeCasts($data['casts']);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return array
     */
    private function getEagers(\Illuminate\Database\Eloquent\Builder $builder): array
    {
        $result = [];

        foreach ($builder->getEagerLoads() as $name => $value) {
            $relation = $builder;
            foreach (explode('.', $name) as $part) {
                $relation = $relation->getRelation($part); // get a relation without "constraints"
            }
            $referenceRelation = clone $relation;

            $value($relation); // apply closure
            $result[$name] = $this->packQueryBuilder($relation->getQuery()->getQuery());

            $relation->getQuery()->getModel()->newInstance()->with($name)->getEagerLoads()[$name]($referenceRelation);
            $this->cleanStaticConstraints($result[$name], $this->packQueryBuilder($referenceRelation->getQuery()->getQuery()));
        }

        return $result;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array $eagers
     * @return void
     */
    private function setEagers(\Illuminate\Database\Eloquent\Builder $builder, array $eagers): void
    {
        foreach ($eagers as &$value) {
            $value = function ($query) use ($value) {
                // Input argument may be different depends on context
                while (! ($query instanceof \Illuminate\Database\Query\Builder)) {
                    $query = $query->getQuery();
                }

                $this->unpackQueryBuilder($value, $query);
            };
        }
        unset($value);

        $builder->setEagerLoads($eagers);
    }

    /**
     * @param array $packedQueryBuilder
     * @param array $packedReferenceQueryBuilder
     * @return void
     */
    private function cleanStaticConstraints(array &$packedQueryBuilder, array $packedReferenceQueryBuilder): void
    {
        $properties = [
            'aggregate', 'columns', 'distinct', 'wheres', 'groups', 'havings', 'orders', 'limit', 'offset', 'unions',
            'unionLimit', 'unionOffset', 'unionOrders', 'joins', 'groupLimit',
        ];

        foreach ($properties as $property) {
            if (! is_array($packedQueryBuilder[$property] ?? null)) {
                continue;
            }

            foreach ($packedQueryBuilder[$property] as $key => $item) {
                if (in_array($item, (array) ($packedReferenceQueryBuilder[$property] ?? null), true)) {
                    unset($packedQueryBuilder[$property][$key]);
                }
            }
        }

        foreach ($packedQueryBuilder['bindings'] as $binding => $data) {
            if (! is_array($data)) {
                continue; // just in case ;)
            }

            foreach ($data as $key => $value) {
                if (
                    isset($packedReferenceQueryBuilder['bindings'][$binding][$key])
                    && $packedReferenceQueryBuilder['bindings'][$binding][$key] === $value
                ) {
                    unset($packedQueryBuilder['bindings'][$binding][$key]);
                }
            }
        }
    }
}
