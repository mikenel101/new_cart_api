<?php

namespace MikesLumenRepository\Criterias;

use Prettus\Repository\Criteria\RequestCriteria as PrettusRequestCriteria;
use Prettus\Repository\Contracts\RepositoryInterface;
use MikesLumenBase\Utils\AuthHelper;

class AuthCriteria extends PrettusRequestCriteria
{

    /**
     * Apply criteria in query repository
     *
     * @param Builder|Model $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     * @throws \Exception
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $authHelper = new AuthHelper($this->request);
        $role = $authHelper->getRequestRole();
        $route = $authHelper->getRoute();
        $method = $authHelper->getRequestMethodLowerCase();

        $shopId = $authHelper->getRequstShopId();
        $userId = $authHelper->getRequstUserId();

        if ($authHelper->isUserOnly($role, $route, $method) && method_exists($model->getModel(), AuthHelper::SCOPE_METHOD_ISUSER)) {
            $model = $model->ownedBy($userId);
        }
        if ($authHelper->isShopOnly($role, $route, $method) && method_exists($model->getModel(), AuthHelper::SCOPE_METHOD_ISSHOP)) {
            $model = $model->inShop($shopId);
        }
        if ($authHelper->isPublicOnly($role, $route, $method) && method_exists($model->getModel(), AuthHelper::SCOPE_METHOD_ISPUBLIC)) {
            $model = $model->public(1);
        }
        if ($authHelper->isAuthTypeOnly($role, $route, $method) && method_exists($model->getModel(), AuthHelper::SCOPE_METHOD_ISAUTHTYPE)) {
            $model = $model->editable(1);
        }

        return $model;
    }
}
