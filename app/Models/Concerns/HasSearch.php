<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * A search() scope for an index page or a list on one: what has every word of the text in one of its searchable
 * columns, in any order, whatever the case, so "grace hop" finds Grace Hopper and "seed swap" a seed circle that's
 * about swapping. The model says which columns: at least its name and description, or what stands for them.
 */
trait HasSearch
{
    /**
     * The columns a search looks through, e.g. ['name', 'description'].
     *
     * @return non-empty-list<string>
     */
    abstract protected function searchableColumns(): array;

    /**
     * NOTE: the columns are qualified with the model's table, so a search through a relation (e.g. a circle's members,
     * joined to circle_user) can't be ambiguous.
     */
    #[Scope]
    protected function search(Builder $query, string $text): void
    {
        foreach (explode(' ', Str::squish($text)) as $word) {
            $query->whereAny($this->qualifyColumns($this->searchableColumns()), 'like', "%{$word}%");
        }
    }
}
