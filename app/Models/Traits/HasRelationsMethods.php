<?php

namespace App\Models\Traits;

use App\Helpers\PhpFileHelper;
use App\Helpers\RelationsHelper;
use App\Models\Upload;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

trait HasRelationsMethods
{
    public function getDefinedUploadRelations()
    {
        return $this->getDefinedRelations()->filter(
            function ($dr) {
                return $dr->model_class === Upload::class;
            }
        );
    }

    public function getDefinedRelations()
    {
        $class = new ReflectionClass($this);
        $methods = collect($class->getMethods(ReflectionMethod::IS_PUBLIC));
        $relationsTypes = RelationsHelper::getRelationTypes();
        $savedRelationsDefinitionsLines = collect();

        return $methods->filter(
            function ($method) use ($relationsTypes, $savedRelationsDefinitionsLines) {
                $methodStartLine = $method->getStartLine();
                $methodEndLine = $method->getEndLine();
                $filePath = $method->getFileName();
                $fileLines = file($filePath);
                $importsLines = PhpFileHelper::getImportLines($filePath);
                $namespace = PhpFileHelper::getNameSpace($filePath);
                $methodLines = collect(array_slice($fileLines, $methodStartLine, $methodEndLine - $methodStartLine + 1));

                return get_class($this) === $method->class && $methodLines->some(
                    function ($line) use ($relationsTypes, $method, $savedRelationsDefinitionsLines, $importsLines, $namespace) {
                        $relationType = $relationsTypes->first(
                            function ($rt) use ($line) {
                                return Str::contains($line, '$this->'.$rt->method.'(');
                            }
                        );
                        if (! is_null($relationType)) {
                            $savedRelationsDefinitionsLines->add(
                                (object) [
                                    'name' => $method->name,
                                    'type' => $relationType->name,
                                    'import_lines' => $importsLines,
                                    'namespace' => $namespace,
                                    'line' => $line,
                                ]
                            );

                            return true;
                        }

                        return false;
                    }
                );
            }
        )->map(
            function ($method) use ($savedRelationsDefinitionsLines) {
                $savedRelationsDefinitionsLine = $savedRelationsDefinitionsLines->firstWhere('name', $method->name);
                $model = Str::of($savedRelationsDefinitionsLine->line)->betweenFirst('(', ')')->explode(',')->first();
                $modelClassName = Str::of($model)->explode('::')->first();
                $isFullModelClassName = Str::contains($modelClassName, '\\');
                $modelClass = '';

                if ($isFullModelClassName) {
                    $modelClass = Str::of($modelClassName)->replace("'", '')->replace('"', '')->toString();
                    $modelClassName = Str::of($modelClass)->explode('\\')->last();
                } else {
                    $importLine = $savedRelationsDefinitionsLine->import_lines->first(
                        function ($il) use ($modelClassName) {
                            return Str::contains($il, $modelClassName);
                        }
                    );
                    if (is_null($importLine)) {
                        $importLine = "use $savedRelationsDefinitionsLine->namespace\\$modelClassName;";
                    }
                    $modelClass = eval("$importLine \n return $model;");
                }

                return (object) [
                    'name' => $method->name,
                    'type' => $savedRelationsDefinitionsLine->type,
                    'model' => $modelClassName,
                    'model_class' => $modelClass,
                ];
            }
        );
    }
}
