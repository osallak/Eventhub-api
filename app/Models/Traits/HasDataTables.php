<?php

namespace App\Models\Traits;

use App\Enums\LinkOperator;
use App\Models\Classes\DataTableParams;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait HasDataTables
{
    public function scopeReadPermission($query)
    {
        $readPermissions = Auth::user()->allTableReadPermissions($this->getTable())->get();
        $readAllPermission = $readPermissions->contains(
            function ($permission) {
                return $permission->name === $this->getTable().'.read' || $permission->name === $this->getTable().'.*';
            }
        );
        if ($readAllPermission) {
            return $query;
        } else {
            $ids = $readPermissions->map(
                function ($permission) {
                    $splitedItems = collect(explode('.', $permission->name));
                    $id = $splitedItems->first(
                        function ($value) {
                            return is_numeric($value);
                        }
                    );

                    return ! is_null($id) ? (int) $id : null;
                }
            )->filter(
                function ($id) {
                    return ! is_null($id);
                }
            )->toArray();

            return $query->whereIn($this->getTable().'.id', $ids);
        }
    }

    public function scopeDataTableSort($query, string $orderColumn, string $orderDir)
    {

        if (Str::of($orderColumn)->contains('.')) {
            $orderItems = explode('.', $orderColumn);
            $relation = $orderItems[0];
            $column = Str::snake($orderItems[1]);
            $subQuery = $this->$relation();

            switch (true) {
                case $this->$relation() instanceof BelongsTo || $this->$relation() instanceof HasOne:
                    $parentKey = $this->$relation() instanceof BelongsTo ? $this->$relation()->getQualifiedOwnerKeyName() : $this->$relation()->getQualifiedParentKeyName();
                    $subQuery = $this->$relation()->getRelated()->select($column)->whereColumn($parentKey, $this->$relation()->getQualifiedForeignKeyName());
                    break;
                case $this->$relation() instanceof BelongsToMany:
                    $pivotTable = explode('.', $this->$relation()->getQualifiedRelatedPivotKeyName())[0];
                    $localForeingKey = $this->getTable().'.'.$this->$relation()->getParentKeyName();
                    $subQuery = $this->$relation()->getRelated()->select($column)->join($pivotTable, $this->$relation()->getQualifiedRelatedPivotKeyName(), '=', $this->$relation()->getQualifiedRelatedKeyName())->whereColumn($this->$relation()->getQualifiedForeignPivotKeyName(), $localForeingKey)->orderBy($column, $orderDir)->limit(1);
                    break;
                case $this->$relation() instanceof HasMany:
                    $subQuery = $this->$relation()->getRelated()->select($column)->whereColumn($this->$relation()->getQualifiedForeignKeyName(), $this->$relation()->getQualifiedParentKeyName())->orderBy($column, $orderDir)->limit(1);
                    break;
            }

            $query->orderBy($subQuery, $orderDir);
        } else {
            return $query->orderBy(
                Str::snake($orderColumn),
                $orderDir
            );
        }
    }

    public function scopeDataTableFilter(Builder $query, $column, $operatorWithValue, string $LinkOperator = LinkOperator::AND->value)
    {
        $aggregateFunctions = ['count(', 'avg(', 'sum(', 'min(', 'max('];
        $aggregateColumns = collect($query->getQuery()->columns)->filter(
            function ($expression) use ($aggregateFunctions, $query) {
                return is_string($expression) && Str::of($expression)->contains($aggregateFunctions) || property_exists($expression, 'value') && Str::of($expression->getValue($query->getGrammar()))->contains($aggregateFunctions);
            }
        )->map(
            function ($expression) use ($query) {
                if (is_string($expression)) {
                    return Str::of(Arr::last(explode('as', $expression)))->replace('`', '')->replace('"', '')->replace("'", '')->trim()->value();
                } elseif (property_exists($expression, 'value')) {
                    return Str::of(Arr::last(explode('as', $expression->getValue($query->getGrammar()))))->replace('`', '')->replace('"', '')->replace("'", '')->trim()->value();
                }
            }
        );
        $column = Str::snake($column);

        if (! $aggregateColumns->contains($column)) {
            if (Str::of($column)->contains('.')) {
                $filterItems = explode('.', $column);
                $relation = $filterItems[0];
                $relatedTable = $this->$relation()->getRelated()->getTable();
                $subQuery = $this->$relation();

                switch (true) {
                    case $this->$relation() instanceof BelongsTo || $this->$relation() instanceof HasOne:
                        $parentKey = $this->$relation() instanceof BelongsTo ? $this->$relation()->getQualifiedOwnerKeyName() : $this->$relation()->getQualifiedParentKeyName();
                        $subQuery = $this->$relation()->getRelated()->selectRaw('count(*)')->whereColumn($parentKey, $this->$relation()->getQualifiedForeignKeyName());
                        break;
                    case $this->$relation() instanceof BelongsToMany:
                        $pivotTable = explode('.', $this->$relation()->getQualifiedRelatedPivotKeyName())[0];
                        $localForeingKey = $this->getTable().'.'.$this->$relation()->getParentKeyName();
                        $subQuery = $this->$relation()->getRelated()->selectRaw('count(*)')->join($pivotTable, $this->$relation()->getQualifiedRelatedPivotKeyName(), '=', $this->$relation()->getQualifiedRelatedKeyName())->whereColumn($this->$relation()->getQualifiedForeignPivotKeyName(), $localForeingKey);
                        break;
                    case $this->$relation() instanceof HasMany:
                        $subQuery = $this->$relation()->getRelated()->selectRaw('count(*)')->whereColumn($this->$relation()->getQualifiedForeignKeyName(), $this->$relation()->getQualifiedParentKeyName());
                        break;
                }

                if ($operatorWithValue->operator == 'in') {
                    $subQuery->whereIn($relatedTable.'.'.$filterItems[1], $operatorWithValue->value);
                } else {
                    $subQuery->where($relatedTable.'.'.$filterItems[1], $operatorWithValue->operator, $operatorWithValue->value);
                }
                if (property_exists($operatorWithValue, 'null') && is_bool($operatorWithValue->null)) {
                    if ($operatorWithValue->null) {
                        $subQuery->orWhereNull($relatedTable.'.'.$filterItems[1]);
                    } else {
                        $subQuery->orWhereNotNull($relatedTable.'.'.$filterItems[1]);
                    }
                }
                if ($LinkOperator === LinkOperator::AND->value) {
                    $query->whereRaw('('.$subQuery->toSql().') > 0', [$operatorWithValue->value]);
                } else {
                    $query->orWhereRaw('('.$subQuery->toSql().') > 0', [$operatorWithValue->value]);
                }
            } else {
                if ($operatorWithValue->operator == 'in') {
                    if ($LinkOperator === LinkOperator::AND->value) {
                        $query->whereIn($column, $operatorWithValue->value);
                    } else {
                        $query->orWhereIn($column, $operatorWithValue->value);
                    }
                } else {
                    if ($LinkOperator === LinkOperator::AND->value) {
                        $query->where($column, $operatorWithValue->operator, $operatorWithValue->value);
                    } else {
                        $query->orWhere($column, $operatorWithValue->operator, $operatorWithValue->value);
                    }
                }
                if (property_exists($operatorWithValue, 'null') && is_bool($operatorWithValue->null)) {
                    if ($operatorWithValue->null) {
                        if ($LinkOperator === LinkOperator::AND->value) {
                            $query->whereNull($column);
                        } else {
                            $query->orWhereNull($column);
                        }
                    } else {
                        if ($LinkOperator === LinkOperator::AND->value) {
                            $query->whereNotNull($column);
                        } else {
                            $query->orWhereNotNull($column);
                        }
                    }
                }
            }
        } else {
            if ($operatorWithValue->operator == 'in') {
                if ($LinkOperator === LinkOperator::AND->value) {
                    $query->having($column, 'in', $operatorWithValue->value);
                } else {
                    $query->orHaving($column, 'in', $operatorWithValue->value);
                }
            } else {
                if ($LinkOperator === LinkOperator::AND->value) {
                    $query->having($column, $operatorWithValue->operator, value: $operatorWithValue->value);
                } else {
                    $query->orHaving($column, $operatorWithValue->operator, value: $operatorWithValue->value);
                }
            }
            if (property_exists($operatorWithValue, 'null') && is_bool($operatorWithValue->null)) {
                if ($operatorWithValue->null) {
                    if ($LinkOperator === LinkOperator::AND->value) {
                        $query->havingNull($column);
                    } else {
                        $query->orHavingNull($column);
                    }
                } else {
                    if ($LinkOperator === LinkOperator::AND->value) {
                        $query->havingNotNull($column);
                    } else {
                        $query->orHavingNotNull($column);
                    }
                }
            }
        }

        return $query;
    }

    public function dataTablePagination($query, $perPage = 50)
    {
        return $query->paginate($perPage);
    }

    public function filter($query, $filter, ?string $LinkOperator = null)
    {
        collect($filter->items)->each(
            function ($item) use ($query, $filter, $LinkOperator) {
                if (property_exists($item, 'linkOperator')) {
                    if ($LinkOperator === LinkOperator::OR->value) {
                        $query->orWhere(
                            function ($q) use ($item) {
                                $this->filter($q, $item, $item->linkOperator);
                            }
                        );
                    } else {
                        $query->where(
                            function ($q) use ($item) {
                                $this->filter($q, $item, $item->linkOperator);
                            }
                        );
                    }
                } else {
                    $column = $item->columnField;
                    $linkOperator = property_exists($filter, 'linkOperator') ? $filter->linkOperator : LinkOperator::AND->value;
                    $operatorWithValue = $this->getOperatorAndValue($item->operatorValue, property_exists($item, 'value') ? $item->value : null);
                    $query->dataTableFilter($column, $operatorWithValue, $linkOperator);
                }
            }
        );
    }

    public function scopeDataTable($query, DataTableParams $dataTableParams)
    {
        if ($dataTableParams->checkPermission) {
            $query->readPermission();
        }

        if ($dataTableParams->hasOrderParam()) {
            $query->dataTableSort($dataTableParams->orderColumn, $dataTableParams->orderDir);
        }

        if ($dataTableParams->hasFilterParam()) {
            $this->filter($query, $dataTableParams->filterParam);
        }

        return $query;
    }

    public function getOperatorAndValue(string $operatorKey, ?string $value)
    {
        $operators = [
            'equals' => (object) [
                'operator' => '=',
                'value' => $value,
            ],
            'contains' => (object) [
                'operator' => 'like',
                'value' => '%'.$value.'%',
            ],
            'startsWith' => (object) [
                'operator' => 'like',
                'value' => $value.'%',
            ],
            'endsWith' => (object) [
                'operator' => 'like',
                'value' => '%'.$value,
            ],
            'isEmpty' => (object) [
                'operator' => '=',
                'value' => '',
                'null' => true,
            ],
            'isNotEmpty' => (object) [
                'operator' => '<>',
                'value' => '',
                'null' => false,
            ],
            'isAnyOf' => (object) [
                'operator' => 'in',
                'value' => explode(',', $value),
            ],
        ];

        return $operators[$operatorKey];
    }
}
