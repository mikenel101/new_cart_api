<?php

namespace MikesLumenRepository\Criterias;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Relations\Relation;

class TranslationCriteria implements CriteriaInterface
{
    protected $translationModel;

    public function __construct($translationModel)
    {
        $this->translationModel = $translationModel;
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
        return $model->with(['translations' =>
            function (Relation $query) {
                $config = app()->make('config');
                $translationModel = $this->translationModel;
                $traslationTable = app()->make($translationModel)->getTable();
                $localeKey = $config->get('translatable.locale_key', 'locale');
                $query->where($traslationTable.'.'.$localeKey, app('translator')->getLocale());
                if ($config->get('translatable.use_fallback')) {
                    return $query->orWhere($traslationTable.'.'.$localeKey, $config->get('translatable.fallback_locale'));
                }
            },
        ]);
    }
}
