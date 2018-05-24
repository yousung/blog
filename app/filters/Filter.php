<?php

namespace App\filters;

use Illuminate\Http\Request;

abstract class Filter
{
    protected $request;
    protected $builder;

    protected $filters = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply($builder)
    {
        $this->builder = $builder;

        if (method_exists($this, 'init')) {
            $this->init();
        }

        foreach ($this->getFilter() as $filter => $value) {
            $filter = camel_case($filter);
            if (method_exists($this, $filter)) {
                $this->$filter($value);
            }
        }

        return $this->builder;
    }

    public function getFilter()
    {
        return array_filter($this->request->only($this->filters));
    }

    protected function hasFilter($filter): bool
    {
        return method_exists($this, $filter) && $this->request->has($filter);
    }
}
