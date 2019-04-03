<?php
namespace MikesLumenBase\Observers;

use Illuminate\Support\Facades\DB;
use MikesLumenBase\Models\Audit;
use MikesLumenRepository\Helpers\UuidHelper;

class AuditableTraitObserver
{
    /**
     * Model's creating event hook.
     *
     * @param MikesLumenBase\Traits\AuditableTrait $model
     */
    public function creating($model)
    {
        $model->created_by = UuidHelper::toBin($this->getAuditId());

        $model->updated_by = UuidHelper::toBin($this->getAuditId());
    }

    /**
     * Model's updating event hook.
     *
     * @param MikesLumenBase\Traits\AuditableTrait $model
     */
    public function updating($model)
    {
        $model->updated_by = UuidHelper::toBin($this->getAuditId());
    }

    public function getAuditId()
    {
        if (app()->runningInConsole()) {
            return Audit::SYSTEM_AUDIT_ID;
        } else {
            $userId = app(\Illuminate\Http\Request::class)->header('X-UserId');
            if (!$userId) {
                return null;
            } else {
                return $userId;
            }
        }
    }
}
