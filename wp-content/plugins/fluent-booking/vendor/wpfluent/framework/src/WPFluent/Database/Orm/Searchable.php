<?php

namespace FluentBooking\Framework\Database\Orm;

use Exception;

trait Searchable
{
    /**
     * Searchable columns.
     * 
     * @var array
     */
    protected $searchableColumns = [];

    /**    
     * Search the model in a case-insensitive manner.
     *
     * @param \FluentBooking\Framework\Database\Orm\Builder $query
     * @param string $value
     * @return \FluentBooking\Framework\Database\Orm\Builder
     * @throws \Exception
     */
    public function scopeSearch($query, $value)
    {
        if (empty($this->searchableColumns)) {
            throw new Exception(
            	'No searchable columns were defined in ' . get_class($this) . '.'
            );
        }

        $value = strtolower($value);

        return $query->where(function($query) use ($value) {
            foreach ($this->searchableColumns as $column) {
                $query->orWhereRaw(
                	'LOWER(' . $column . ') LIKE ?', ['%' . $value . '%']
                );
            }
        });
    }
}
