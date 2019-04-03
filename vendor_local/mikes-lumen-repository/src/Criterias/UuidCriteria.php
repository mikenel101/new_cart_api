<?php

namespace MikesLumenRepository\Criterias;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use MikesLumenRepository\Helpers\UuidHelper;

class UuidCriteria implements CriteriaInterface
{
    protected $uuidField;
    protected $uuidValue;

    public function __construct($uuidField, $uuidValue)
    {
        $this->uuidField = $uuidField;
        $this->uuidValue = $uuidValue;
    }

    /**
     * Apply criteria in query repository
     *
     * @param                     $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where($this->uuidField, UuidHelper::toUuidExpression($this->uuidValue));
    }
}
