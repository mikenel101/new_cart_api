<?php

namespace MikesLumenRepository\Criterias;

use Prettus\Repository\Criteria\RequestCriteria as PrettusRequestCriteria;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Facades\DB;
use MikesLumenRepository\Helpers\UuidHelper;

class RequestCriteria extends PrettusRequestCriteria
{
    /**
     * Decode a search value with colon or semicolon
     * @param  string $value
     * @return string
     */
    protected function decodeValueForSeparators($value)
    {
        return str_replace(array('__COLON__', '__SEMICOLON__'), array(':', ';'), $value);
    }

    /**
     * @param $search
     *
     * @return array
     */
    protected function parserSearchData($search)
    {
        $searchData = [];
        if (stripos($search, ':')) {
            $fields = explode(';', $search);
            foreach ($fields as $row) {
                try {
                    list($field, $value) = explode(':', $row);
                    $searchData[$field] = $this->decodeValueForSeparators($value);
                } catch (\Exception $e) {
                    //Surround offset error
                }
            }
        }
        return $searchData;
    }

    /**
     * @param $search
     *
     * @return null
     */
    protected function parserSearchValue($search)
    {
        if (stripos($search, ';') || stripos($search, ':')) {
            $values = explode(';', $search);
            foreach ($values as $value) {
                $s = explode(':', $value);
                if (count($s) == 1) {
                    return $this->decodeValueForSeparators($s[0]);
                }
            }
            return null;
        }
        return $this->decodeValueForSeparators($search);
    }

    /**
     * Apply criteria in query repository
     *
     * @param Builder|Model       $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     * @throws \Exception
     */
    public function apply($model, RepositoryInterface $repository)
    {
        if (is_callable([$repository, 'settingFieldSearchable'])) {
            $repository->settingFieldSearchable($this->request);
        }

        $fieldsSearchable = $repository->getFieldsSearchable();
        $searchJoin = $this->request->get(config('repository.criteria.params.searchJoin', 'searchJoin'), null);
        $search = $this->request->get(config('repository.criteria.params.search', 'search'), null);
        $searchFields = $this->request->get(config('repository.criteria.params.searchFields', 'searchFields'), null);
        $filter = $this->request->get(config('repository.criteria.params.filter', 'filter'), null);
        $orderBy = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);
        $sortedBy = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');
        $with = $this->request->get(config('repository.criteria.params.with', 'with'), null);
        $sortedBy = !empty($sortedBy) ? $sortedBy : 'asc';
        $scopeSearchable = isset($repository->magicSearchable) ? $repository->magicSearchable : null;
        $virtualFieldsSearchable = isset($repository->virtualFieldsSearchable) ? $repository->virtualFieldsSearchable : null;

        $searchData = $this->parserSearchData($search);
        $searchValue = $this->parserSearchValue($search);

        $modelForceAndWhere = isset($repository->modelForceAndWhere)
            ? $repository->modelForceAndWhere
            : $searchJoin ? strtolower($searchJoin) === 'and' : true;

        if (!is_null($searchValue) && $searchValue != '' && is_array($scopeSearchable) && count($scopeSearchable)) {
            foreach ($scopeSearchable as $searchMethod) {
                if (is_callable([$repository, $searchMethod])) {
                    $model = $repository->{$searchMethod}($model, $searchValue);
                }
            }

            $searchValue = null;
        }

        if ($searchData && is_array($virtualFieldsSearchable) && count($virtualFieldsSearchable)) {
            foreach ($virtualFieldsSearchable as $field => $searchMethod) {
                if (is_callable([$repository, $searchMethod])
                    && isset($searchData[$field]) && $searchData[$field] != '') {
                    $model = $repository->{$searchMethod}($model, $searchData[$field]);
                    $isFirstField = false;
                }
                unset($searchData[$field]);
            }
        }

        if ($searchData && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            $searchFields = is_array($searchFields) || is_null($searchFields)
                ? $searchFields : ( empty($searchFields) ? null : explode(';', $searchFields) );
            $fields = $this->parserFieldsSearch($fieldsSearchable, $searchFields);
            $isFirstField = true;

            if ($modelForceAndWhere) {
                $model = $this->buildQuery($model, $fields, $searchValue, $searchData, $isFirstField, $modelForceAndWhere);
            } else {
                $model = $model->where(function ($model) use (
                    $fields,
                    $searchValue,
                    $searchData,
                    $isFirstField,
                    $modelForceAndWhere) {
                    return $this->buildQuery($model, $fields, $searchValue, $searchData, $isFirstField, $modelForceAndWhere);
                });
            }
        }

        if (isset($orderBy) && !empty($orderBy)) {
            $model->getQuery()->orders = null;

            $split = explode('|', $orderBy);
            if (count($split) > 1) {
                /*
                 * ex.
                 * products|description -> join products on current_table.product_id = products.id order by description
                 *
                 * products:custom_id|products.description -> join products on current_table.custom_id = products.id order
                 * by products.description (in case both tables have same column name)
                 */
                $table = $model->getModel()->getTable();
                $sortTable = $split[0];
                $sortColumn = $split[1];

                $split = explode(':', $sortTable);
                if (count($split) > 1) {
                    $sortTable = $split[0];
                    $keyName = $table.'.'.$split[1];
                } else {
                    /*
                     * If you do not define which column to use as a joining column on current table, it will
                     * use a singular of a join table appended with _id
                     *
                     * ex.
                     * products -> product_id
                     */
                    $prefix = rtrim($sortTable, 's');
                    $keyName = $table.'.'.$prefix.'_id';
                }

                $model = $model
                    ->leftJoin($sortTable, $keyName, '=', $sortTable.'.id')
                    ->orderBy($sortColumn, $sortedBy)
                    ->addSelect($table.'.*');
            } else {
                $model = $model->orderBy($orderBy, $sortedBy);
            }
        }

        if (isset($filter) && !empty($filter)) {
            if (is_string($filter)) {
                $filter = explode(';', $filter);
            }

            $model = $model->select($filter);
        }

        if ($with) {
            $with = explode(';', $with);
            $customWith = !is_null($model->getModel()->customWith) ? $model->getModel()->customWith : [];
            $with = array_diff($with, $customWith);
            $with = array_values($with);
            $model = $model->with($with);
        }

        return $model;
    }

    protected function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    protected function escapeSearchValueForDB($value) {
        // https://qiita.com/bngper/items/41022bd9ac7541770570
        // For MySQL with utf8_unicode_ci in Like query, we need to convert back slash and percent to two-byte characters
        return str_replace(array('\\', '_', '%'), array('＼', '\\_', '％'), $value);
        // return str_replace(array('\\', '_', '%'), array('\\\\', '\\_', '\\%'), $value);
    }

    protected function buildQuery($query, $fields, $search, $searchData, $isFirstField, $modelForceAndWhere)
    {
        foreach ($fields as $field => $condition) {
            if (is_numeric($field)) {
                $field = $condition;
                $condition = "=";
            }

            $value = null;

            $condition = trim(strtolower($condition));

            $isTimestamp = false;
            if ($condition == "timestamp") {
                $isTimestamp = true;
                $condition = "=";
            }

            if (isset($searchData[$field])) {
                $value = $searchData[$field];
                $value = $this->formatSearchValue($condition, $value);
            } else {
                if (!is_null($search)) {
                    $value = $search;
                    $value = $this->formatSearchValue($condition, $value);
                }
            }

            if ($this->endsWith($field, '__gt')) {
                $field = substr($field, 0, strlen($field) - strlen('__gt'));
                $condition = '>';
            } else if ($this->endsWith($field, '__gte')) {
                $field = substr($field, 0, strlen($field) - strlen('__gte'));
                $condition = '>=';
            } else if ($this->endsWith($field, '__lt')) {
                $field = substr($field, 0, strlen($field) - strlen('__lt'));
                $condition = '<';
            } else if ($this->endsWith($field, '__lte')) {
                $field = substr($field, 0, strlen($field) - strlen('__lte'));
                $condition = '<=';
            }

            if ($value && $isTimestamp) {
                $value = DB::raw("FROM_UNIXTIME(${value})");
            }

            $relation = null;
            if (stripos($field, '.')) {
                $explode = explode('.', $field);
                $field = array_pop($explode);
                $relation = implode('.', $explode);
            }
            $modelTableName = $query->getModel()->getTable();
            if ($isFirstField || $modelForceAndWhere) {
                if (!is_null($value)) {
                    if (!is_null($relation)) {
                        $query = $query->whereHas($relation, function ($query) use ($field, $condition, $value) {
                            $this->buildWhere($query, $field, $condition, $value);
                        });
                    } else {
                        if ($query && method_exists($query->getModel(), 'isTranslationAttribute')
                            && $query->getModel()->isTranslationAttribute($field)
                        ) {
                            $query = $this->buildTranslationWhere($query, $field, $condition, $value);
                        } else {
                            $query = $this->buildWhere($query, $modelTableName.'.'.$field, $condition, $value);
                        }
                    }
                    $isFirstField = false;
                }
            } else {
                if (!is_null($value)) {
                    if (!is_null($relation)) {
                        $query = $query->orWhereHas($relation, function ($query) use ($field, $condition, $value) {
                            $this->buildWhere($query, $field, $condition, $value);
                        });
                    } else {
                        $query = $query->orWhere(function ($_query) use ($modelTableName, $field, $condition, $value) {
                            $this->buildWhere($_query, $modelTableName.'.'.$field, $condition, $value);
                        });
                    }
                }
            }
        }

        return $query;
    }

    protected function buildWhere($query, $field, $condition, $value)
    {
        $model = $query->getModel();
        if ($condition == "in") {
            if ($model->isUuidAttribute($field)) {
                foreach ($value as &$id) {
                    if (UuidHelper::isUuidString($id)) {
                        $id = UuidHelper::toUuidExpression($id);
                    }
                }
            }
            $query = $query->whereIn($field, $value);
        } else {
            if ($model->isUuidAttribute($field) && UuidHelper::isUuidString($value)) {
                $value = UuidHelper::toUuidExpression($value);
            }
            $query = $query->where($field, $condition, $value);
        }

        return $query;
    }

    protected function buildTranslationWhere($query, $field, $condition, $value)
    {
        if ($query instanceof \Illuminate\Database\Eloquent\Model) {
            $query = $query->query();
        }

        if ($condition == "like") {
            $query = $query->getModel()->scopeWhereTranslationLike($query, $field, $value);
        } else {
            $query = $query->getModel()->scopeWhereTranslation($query, $field, $value);
        }

        return $query;
    }

    protected function parserFieldsSearch(array $fields = [], array $searchFields = null)
    {
        if (!is_null($searchFields) && count($searchFields)) {
            $acceptedConditions = config('repository.criteria.acceptedConditions', [
                '=',
                'like',
                'timestamp'
            ]);
            $originalFields = $fields;
            $fields = [];
            foreach ($searchFields as $index => $field) {
                $field_parts = explode(':', $field);
                $temporaryIndex = array_search($field_parts[0], $originalFields);
                if (count($field_parts) == 2) {
                    if (in_array($field_parts[1], $acceptedConditions)) {
                        unset($originalFields[$temporaryIndex]);
                        $field = $field_parts[0];
                        $condition = $field_parts[1];
                        $originalFields[$field] = $condition;
                        $searchFields[$index] = $field;
                    }
                }
            }
            foreach ($originalFields as $field => $condition) {
                if (is_numeric($field)) {
                    $field = $condition;
                    $condition = "=";
                }
                if (in_array($field, $searchFields)) {
                    $fields[$field] = $condition;
                }
            }
            if (count($fields) == 0) {
                throw new \Exception(trans('repository::criteria.fields_not_accepted', ['field' => implode(',', $searchFields)]));
            }
        }
        return $fields;
    }

    /**
     * search パラメータのキーと値をMapで取得します。
     * @return mixed
     */
    public function getSearchParams() {
        $search = $this->request->get(config('repository.criteria.params.search', 'search'), null);

        return $this->parserSearchData($search);
    }

    /**
     * @param string $condition
     * @param $value
     * @return array|mixed|string
     */
    protected function formatSearchValue(string &$condition, $value)
    {
        if ($condition == "like" || $condition == "ilike") {
            $value = $this->escapeSearchValueForDB($value);
            $value = "%{$value}%";
        } else if ($condition == "in") {
            $value = explode(',', $value);
        } else if ($condition == "forward") {
            $value = $this->escapeSearchValueForDB($value);
            $value = "{$value}%";
            $condition = 'like';
        } else {
            $value = $value;
        }

        return $value;
    }
}
