<?php

namespace MikesLumenBase\Criterias;

use Prettus\Repository\Criteria\RequestCriteria as PrettusRequestCriteria;
use Prettus\Repository\Contracts\RepositoryInterface;

class CurrencyCriteria extends PrettusRequestCriteria
{
    protected $currency;

    public function __construct(?string $currency)
    {
        $this->currency = $currency;
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
        if ($this->currency) {
            return $model->filterByCurrency($this->currency);
        } else {
            return $model;
        }
    }
}
