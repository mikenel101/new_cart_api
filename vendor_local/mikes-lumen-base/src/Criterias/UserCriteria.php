<?php

namespace MikesLumenBase\Criterias;

use Prettus\Repository\Criteria\RequestCriteria as PrettusRequestCriteria;
use Prettus\Repository\Contracts\RepositoryInterface;

class UserCriteria extends PrettusRequestCriteria
{
    protected $userId;

    public function __construct(?string $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Apply criteria in query repository
     *
     * @param         Builder|Model $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     * @throws \Exception
     */
    public function apply($model, RepositoryInterface $repository)
    {
        if ($this->userId) {
            return $model->filterByUserId($this->userId);
        } else {
            return $model;
        }
    }
}
