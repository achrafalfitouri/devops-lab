<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class FilterHelper
{
    /**
     *
     * @param Builder 
     * @param array 
     * @param array 
     * @return Builder
     */
    public static function applyFilters(Builder $query, array $filters, array $filterableFields): Builder
    {
        foreach ($filterableFields as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        return $query;
    }

    /**
     * 
     *
     * @param Builder 
     * @param string|null 
     * @param array 
     * @return Builder
     */
    public static function applySearch(Builder $query, ?string $search, array $searchableFields): Builder
    {
        if (!empty($search)) {
            $query->where(function ($q) use ($search, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }
        return $query;
    }
}
