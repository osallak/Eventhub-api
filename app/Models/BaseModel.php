<?php

namespace App\Models;

use App\Models\Traits\HasDataTables;
use App\Models\Traits\HasRelationsMethods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BaseModel extends Model
{
    use HasDataTables;
    use HasRelationsMethods;

    // Abstract static property "cacheKey" must be defined in child class
    public static $cacheKey;

    // Can be overridden in child class to prevent auto uploads deletion
    protected bool $deleteUploads = true;

    protected static function booted()
    {
        parent::booted();
        $cacheKey = static::$cacheKey;

        static::created(
            function ($item) use ($cacheKey) {
                $items = Cache::get($cacheKey, collect([]));
                $items->push($item);
                Cache::put($cacheKey, $items);
            }
        );

        static::updated(
            function ($item) use ($cacheKey) {
                $items = Cache::get($cacheKey, collect([]));
                $items = $items->map(
                    function ($i) use ($item) {
                        if ($i->id === $item->id) {
                            return $item;
                        }

                        return $i;
                    }
                );
                Cache::put($cacheKey, $items);
            }
        );

        static::deleted(
            function ($item) use ($cacheKey) {
                $items = Cache::get($cacheKey, collect([]));
                $items = $items->filter(
                    function ($i) use ($item) {
                        return $i->id !== $item->id;
                    }
                );
                Cache::put($cacheKey, $items);

                $uploadRelations = $item->getDefinedUploadRelations();
                if ($item->deleteUploads && $uploadRelations->isNotEmpty()) {
                    $uploadRelations->each(
                        function ($uploadRelation) use ($item) {
                            $relationName = $uploadRelation->name;
                            $upload = $item->$relationName;
                            $upload->delete();
                        }
                    );
                }
            }
        );
    }
}
