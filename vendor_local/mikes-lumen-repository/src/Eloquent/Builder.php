<?php

namespace MikesLumenRepository\Eloquent;

use Illuminate\Database\Eloquent\Builder as BaseBuilder;
use MikesLumenRepository\Helpers\UuidHelper;
use MikesLumenRepository\Models\BaseModel;
use Webpatser\Uuid\Uuid;

class Builder extends BaseBuilder
{
    public function find($id, $columns = ['*'])
    {
        $id = UuidHelper::toUuidExpression($id);
        return parent::find($id, $columns);
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($this->model instanceof BaseModel) {
            /** @var BaseModel $model */
            $model = $this->model;

            if (is_string($column)) {
                if ($model->isUuidAttribute($column) && UuidHelper::isUuidString($value)) {
                    $value = UuidHelper::toUuidExpression($value);
                }
            } else if (is_array($column)) {
                foreach ($column as $key => $columnValue) {
                    if ($model->isUuidAttribute($key) && UuidHelper::isUuidString($columnValue)) {
                        $column[$key] = UuidHelper::toUuidExpression($columnValue);
                    }
                }
            }
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if ($this->model instanceof BaseModel) {
            /** @var BaseModel $model */
            $model = $this->model;

            if ($model->isUuidAttribute($column)) {
                foreach ($values as $key => $value) {
                    if (UuidHelper::isUuidString($value)) {
                        $values[$key] = UuidHelper::toUuidExpression($value);
                    }
                }
            }
        }

        $this->query->whereIn($column, $values, $boolean, $not);

        return $this;
    }

    public function insert(array $values)
    {
        if ($this->model instanceof BaseModel && is_array($values)) {
            $model = $this->model;
            foreach ($values as $key => $value) {
                if ($model->isUuidAttribute($key) && UuidHelper::isUuidString($value)) {
                    $values[$key] = UuidHelper::toUuidExpression($value);
                }
            }
        }

        return parent::insert($values);
    }

    public function bulkInsert(array $valueLists)
    {
        $generatedIds = array();

        if ($this->model instanceof BaseModel && is_array($valueLists)) {
            $model = $this->model;
            foreach ($valueLists as $key => $values) {
                if (is_array($values)) {
                    foreach ($values as $column => $value) {
                        if ($model->isUuidAttribute($column) && UuidHelper::isUuidString($value)) {
                            $valueLists[$key][$column] = UuidHelper::toUuidExpression($value);
                        }
                    }
                    $valueLists[$key]['id'] = Uuid::generate(4)->bytes;
                    $generatedIds[] = bin2hex($valueLists[$key]['id']);
                }
            }
        }
        parent::insert($valueLists);

        return $generatedIds;
    }

    public function update(array $values)
    {
        if ($this->model instanceof BaseModel && is_array($values)) {
            $model = $this->model;
            foreach ($values as $key => $value) {
                if ($model->isUuidAttribute($key) && UuidHelper::isUuidString($value)) {
                    $values[$key] = UuidHelper::toUuidExpression($value);
                }
            }
        }

        return parent::update($values);
    }
}
