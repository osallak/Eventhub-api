<?php

namespace App\Helpers;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelationsHelper
{
    public static function getRelationTypes(): Collection
    {
        $relationsNamespace = 'Illuminate\Database\Eloquent\Relations';

        return collect(ClassFinder::getClassesInNamespace($relationsNamespace))->filter(
            function ($className) use ($relationsNamespace) {
                return ! in_array($className, ["$relationsNamespace\\Pivot", "$relationsNamespace\\Relation"]);
            }
        )->map(
            function ($className) use ($relationsNamespace) {
                $name = Str::replace($relationsNamespace.'\\', '', $className);

                return (object) [
                    'name' => $name,
                    'method' => Str::camel($name),
                    'class' => $className,
                ];
            }
        )->filter(
            function ($relationType) {
                return $relationType->class !== MorphTo::class;
            }
        );
    }
}
