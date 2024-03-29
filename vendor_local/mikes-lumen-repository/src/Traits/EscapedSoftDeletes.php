<?php
namespace MikesLumenRepository\Traits;

use Illuminate\Database\Eloquent\SoftDeletingScope;

trait EscapedSoftDeletes
{
    /**
     * 削除時にエスケープする属性を取得
     * デフォルトは $escapedDeleteAttributes に定義された値を返す
     *
     */
    protected function getEscapedDeleteAttributes()
    {
        return $this->escapedDeleteAttributes ?? [];
    }

    /**
     * 削除時の値エスケープする
     *
     * @param  $attribute
     * @param  $value
     */
    protected function escapeDeletingValue($attribute, $value)
    {
        $date = new \DateTime();
        return '__' . $date->getTimestamp() . '__' . $value;
    }

    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootEscapedSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }
    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        $this->forceDeleting = true;
        $deleted = $this->delete();
        $this->forceDeleting = false;
        return $deleted;
    }
    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            return $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey())->forceDelete();
        }
        return $this->runSoftDelete();
    }
    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());
        $time = $this->freshTimestamp();
        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];
        $this->{$this->getDeletedAtColumn()} = $time;
        if ($this->timestamps) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        foreach ($this->getEscapedDeleteAttributes() as $attribute) {
            $escapedValue = $this->escapeDeletingValue($attribute, $this->{$attribute});
            $this->{$attribute} = $escapedValue;
            $columns[$attribute] = $escapedValue;
        }

        $query->update($columns);
    }
    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }
        $this->{$this->getDeletedAtColumn()} = null;
        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;
        $result = $this->save();
        $this->fireModelEvent('restored', false);
        return $result;
    }
    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return ! is_null($this->{$this->getDeletedAtColumn()});
    }
    /**
     * Register a restoring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }
    /**
     * Register a restored model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }
    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting()
    {
        return $this->forceDeleting;
    }
    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }
    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->getTable().'.'.$this->getDeletedAtColumn();
    }
}
