<?php

namespace MikesLumenRepository\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository as Repository;
use Prettus\Repository\Events\RepositoryEntityCreated;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\Exceptions\ValidatorException;

abstract class BaseRepository extends Repository
{
    /**
     * Specify Validator class name of Prettus\Validator\Contracts\ValidatorInterface
     *
     * @return null
     * @throws Exception
     */
    public function validator()
    {
        if (isset($this->rules) && !is_null($this->rules) && is_array($this->rules) && !empty($this->rules)) {
            $validator = app('MikesLumenRepository\Validators\LumenValidator');
            $validator->setRules($this->rules);
            return $validator;
        }

        return null;
    }

    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->find($id, $columns);
        $this->resetModel();
        return $this->parserResult($model);
    }

    public function findOrFail($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->findOrFail($id, $columns);
        $this->resetModel();
        return $this->parserResult($model);
    }

    public function move($id, $priorSiblingId)
    {
        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);
        $this->applyCriteria();
        $query = $this->model->getQuery();
        $this->model->__clone();
        $clonedQuery = $this->model->getQuery();

        $this->model->setQuery($query);
        $target = $this->model->findOrFail($id);

        $this->model->setQuery($clonedQuery);
        $beforeOrder = $target->sort_order;
        if (empty($priorSiblingId)) {
            $priorSibling = $this->model->sorted()->first();
            $target->moveBefore($priorSibling);
        } else {
            $priorSibling = $this->model->findOrFail($priorSiblingId);
            $target->moveAfter($priorSibling);
        }

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        $afterOrder = $target->sort_order;
        if (intval($beforeOrder) === intval($afterOrder)) {
            return null;
        }
        return $target;
    }

    public function delete($id)
    {
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $this->applyCriteria();
        $model = $this->model->findOrFail($id);
        $originalModel = clone $model;

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        $deleted = $model->delete();

        event(new RepositoryEntityDeleted($this, $originalModel));

        return $deleted;
    }
}
