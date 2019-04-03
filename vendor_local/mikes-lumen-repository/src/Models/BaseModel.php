<?php

namespace MikesLumenRepository\Models;

use Illuminate\Database\Eloquent\Model;
use MikesLumenRepository\Eloquent\Builder;
use MikesLumenRepository\Helpers\UuidHelper;
use Prettus\Repository\Contracts\Transformable;
use Alsofronie\Uuid\UuidBinaryModelTrait;

class BaseModel extends Model implements Transformable
{
    use UuidBinaryModelTrait;

    /**
     * Indicates if the model should force an auto-incrementeing id.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if ($this->isUuidAttribute($key) && UuidHelper::isUuidString($value)) {
            $value = hex2bin($value);
        }
        parent::setAttribute($key, $value);
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->getTimestamp();
        // return $date->format("Y-m-d\TH:i:s\Z");
    }

    /**
      * @return array
      */
    public function transform()
    {
        return $this->jsonSerialize();
    }

    public function getUuidAttributes()
    {
        $uuids = array('id');
        foreach ($this->attributes as $field => $value) {
            if ($this->isUuidAttribute($field)) {
                $uuids[] = $field;
            }
        }
        return $uuids;
    }

    public function isUuidAttribute($field)
    {
        return preg_match("/_id$|^id$|\.id$|_by$/", $field);
    }

    public function getUuidAttribute($field)
    {
        return UuidHelper::toHex($this->getAttribute($field));
    }

    public function getUuidExpression($field)
    {
        return UuidHelper::toUuidExpression($this->getAttribute($field));
    }

    /**
     * ",xxx,yyy,zzz,"のような形式の文字列で保存されているフィールドの値を配列に変換して返す
     *
     * @param  $field
     * @return array
     */
    public function getListAttribute($field)
    {
        $value = $this->getAttribute($field);
        if (empty($value)) {
            return [];
        }
        $parts = explode(',', $value);
        return array_slice($parts, 1, count($parts) - 2);
    }

    public function toArray()
    {
        $parentArray = parent::toArray();

        foreach ($parentArray as $key => $value) {
            if ($this->isUuidAttribute($key) && UuidHelper::isUuidValue($value)) {
                $parentArray[$key] = (property_exists($this, 'uuidOptimization') && $this::$uuidOptimization)
                ? self::toNormal($value) : bin2hex($value);
            }
        }
        if (isset($parentArray['pivot'])) {
            unset($parentArray['pivot']);
        }
        // Add description to unset Translations
        if (isset($parentArray['translations'])) {
            unset($parentArray['translations']);
        }
        return $parentArray;
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get the attributes that have been changed since last sync.
     * Customize to enforce to update to fire updating event.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] &&
                                 ! $this->originalIsNumericallyEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        if (!empty($this->translatedAttributes) && empty($dirty) && $this->timestamps) {
            foreach ($this->translations as $translation) {
                if ($this->isTranslationDirty($translation)) {
                    // Enforce to update to fire updating event.
                    $dirty[static::UPDATED_AT] = $this->freshTimestamp();
                }
            }
        }

        return $dirty;
    }
}
