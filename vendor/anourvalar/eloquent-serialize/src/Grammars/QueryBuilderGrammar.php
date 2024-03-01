<?php

namespace AnourValar\EloquentSerialize\Grammars;

trait QueryBuilderGrammar
{
    /**
     * Serialize state for \Illuminate\Database\Query\Builder
     *
     * @param \Illuminate\Database\Query\Builder $builder
     * @return array
     */
    protected function packQueryBuilder(\Illuminate\Database\Query\Builder $builder): array
    {
        return array_filter([
            'bindings' => $builder->bindings,
            'aggregate' => $builder->aggregate,
            'columns' => $builder->columns,
            'distinct' => $builder->distinct,
            'from' => $builder->from,
            'wheres' => $this->packWheres($builder->wheres),
            'groups' => $builder->groups,
            'havings' => $builder->havings,
            'groupLimit' => $builder->groupLimit ?? null,
            'orders' => $builder->orders,
            'limit' => $builder->limit,
            'offset' => $builder->offset,
            'unions' => $this->packUnions($builder->unions),
            'unionLimit' => $builder->unionLimit,
            'unionOffset' => $builder->unionOffset,
            'unionOrders' => $builder->unionOrders,

            'joins' => $this->packJoins($builder->joins), // must be the last
        ], fn ($item) => isset($item));
    }

    /**
     * @param array $data
     * @param \Illuminate\Database\Query\Builder $builder
     * @return \Illuminate\Database\Query\Builder
     */
    protected function unpackQueryBuilder(array $data, \Illuminate\Database\Query\Builder $builder): \Illuminate\Database\Query\Builder
    {
        foreach ($data as $key => $value) {
            if ($key == 'wheres') {
                $value = $this->unpackWheres($value, $builder);
            }

            if ($key == 'unions') {
                $value = $this->unpackUnions($value);
            }

            if ($key == 'joins') {
                $value = $this->unpackJoins($value, $builder);
            }

            if (is_array($builder->$key) && is_array($value)) {
                $builder->$key = array_merge_recursive($builder->$key, $value);
            } else {
                $builder->$key = $value;
            }
        }

        return $builder;
    }

    /**
     * @param mixed $wheres
     * @return mixed
     */
    private function packWheres($wheres)
    {
        foreach ($wheres as &$item) {
            if (isset($item['query'])) {
                $item['query'] = $this->packQueryBuilder($item['query']);
            }
        }
        unset($item);

        return $wheres;
    }

    /**
     * @param mixed $unions
     * @return mixed
     */
    private function packUnions($unions)
    {
        if (! is_array($unions)) {
            return $unions;
        }

        foreach ($unions as &$item) {
            if (isset($item['query'])) {
                $item['query'] = $this->pack($item['query']);
            }
        }
        unset($item);

        return $unions;
    }

    /**
     * @param mixed $joins
     * @return mixed
     */
    private function packJoins($joins)
    {
        if (! is_array($joins)) {
            return $joins;
        }

        foreach ($joins as &$item) {
            $item = array_replace(
                ['type' => $item->type, 'table' => $item->table],
                $this->packQueryBuilder($item)
            );
        }
        unset($item);

        return $joins;
    }

    /**
     * @param mixed $wheres
     * @param \Illuminate\Database\Query\Builder $builder
     * @return mixed
     */
    private function unpackWheres($wheres, \Illuminate\Database\Query\Builder $builder)
    {
        foreach ($wheres as &$item) {
            if (isset($item['query'])) {
                $item['query'] = $this->unpackQueryBuilder($item['query'], $builder->newQuery());
            }
        }
        unset($item);

        return $wheres;
    }

    /**
     * @param mixed $unions
     * @return mixed
     */
    private function unpackUnions($unions)
    {
        if (! is_array($unions)) {
            return $unions;
        }

        foreach ($unions as &$item) {
            if (isset($item['query'])) {
                $item['query'] = $this->unpack($item['query']);
            }
        }
        unset($item);

        return $unions;
    }

    /**
     * @param mixed $joins
     * @param \Illuminate\Database\Query\Builder $builder
     * @return mixed
     */
    private function unpackJoins($joins, \Illuminate\Database\Query\Builder $builder)
    {
        if (! is_array($joins)) {
            return $joins;
        }

        foreach ($joins as &$item) {
            $parentQuery = new \Illuminate\Database\Query\JoinClause($builder, $item['type'], $item['table']);
            unset($item['type'], $item['table']);

            $item = $this->unpackQueryBuilder($item, $parentQuery);
        }
        unset($item);

        return $joins;
    }
}
