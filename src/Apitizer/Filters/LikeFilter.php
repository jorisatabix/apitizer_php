<?php

namespace Apitizer\Filters;

use Illuminate\Database\Eloquent\Builder;

class LikeFilter
{
    protected $fields;

    public function __construct($fields)
    {
        $this->fields = is_array($fields) ? $fields : func_get_args();
    }

    public function __invoke(Builder $query, string $value)
    {
        $searchTerm = '%' . $value . '%';

        $query->where(function ($query) use ($searchTerm) {
            foreach ($this->fields as $field) {
                $query->orWhere($field, 'like', $searchTerm);
            }
        });
    }
}
