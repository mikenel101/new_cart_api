<?php

namespace MikesLumenBase\Criterias;

use Prettus\Repository\Criteria\RequestCriteria as PrettusRequestCriteria;
use Prettus\Repository\Contracts\RepositoryInterface;

class ShopCriteria extends PrettusRequestCriteria
{
    protected $shopId;

    public function __construct(?string $shopId)
    {
        $this->shopId = $shopId;
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
        if ($this->shopId) {
            return $model->filterByShopId($this->shopId);
        } else {
            return $model;
        }
    }
}
