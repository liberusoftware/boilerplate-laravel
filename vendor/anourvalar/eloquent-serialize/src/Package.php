<?php

namespace AnourValar\EloquentSerialize;

class Package
{
    /**
     * $var array
     */
    private $data;

    /**
     * @param array $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key = null)
    {
        if (is_null($key)) {
            return $this->data;
        }

        return ($this->data[$key] ?? null);
    }
}
