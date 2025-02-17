<?php

namespace App\Http\Controllers;

use App\Models\Classes\DataTableParams;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @method void afterCreateOne($model, Request $request)
 * @method void afterReadOne($model, Request $request)
 * @method void afterReadAll($models)
 * @method void afterUpdateOne($model, Request $request)
 * @method void afterDeleteOne($model, Request $request)
 */
abstract class CrudController extends Controller
{
    protected $restricted = ['create', 'read_one', 'read_all', 'update', 'delete'];

    abstract protected function getTable();

    abstract protected function getModelClass();

    protected function model()
    {
        return app($this->getModelClass());
    }

    // to override in case of custom query
    protected function getReadAllQuery(): Builder
    {
        return $this->model()->query();
    }

    protected function getDatatableParams(Request $request): DataTableParams
    {
        return new DataTableParams($request->order, $request->filter);
    }

    public function createOne(Request $request)
    {
        try {
            return DB::transaction(
                function () use ($request) {
                    if (in_array('create', $this->restricted)) {
                        $user = $request->user();
                        if (! $user->hasPermission($this->getTable(), 'create')) {
                            return response()->json(
                                [
                                    'success' => false,
                                    'errors' => [__('common.permission_denied')],
                                ]
                            );
                        }
                    }
                    $model = app($this->getModelClass());
                    $customValidationMsgs = method_exists($model, 'validationMessages') ? $model->validationMessages() : [];
                    $validated = $request->validate(app($this->getModelClass())->rules(), $customValidationMsgs);
                    $model = $this->model()->create($validated);

                    if (method_exists($this, 'afterCreateOne')) {
                        $this->afterCreateOne($model, $request);
                    }

                    return response()->json(
                        [
                            'success' => true,
                            'data' => ['item' => $model],
                            'message' => __($this->getTable().'.created'),
                        ]
                    );
                }
            );
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => Arr::flatten($e->errors())]);
        } catch (\Exception $e) {
            Log::error('Error caught in function CrudController.createOne: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    public function readOne($id, Request $request)
    {
        try {
            if (in_array('read_one', $this->restricted)) {
                $user = $request->user();
                if (! $user->hasPermission($this->getTable(), 'read', $id)) {
                    return response()->json(
                        [
                            'success' => false,
                            'errors' => [__('common.permission_denied')],
                        ]
                    );
                }
            }

            // Retrieve item from cache if exists, otherwise retrieve from database
            if (property_exists($this->getModelClass(), 'cacheKey') && Str::of($this->getModelClass()::$cacheKey)->isNotEmpty()) {
                $cacheKey = $this->getModelClass()::$cacheKey;
                if (! Cache::has($cacheKey)) {
                    $items = $this->model()->all();
                    Cache::put($cacheKey, $items);
                } else {
                    $items = Cache::get($cacheKey);
                }
            }

            if (isset($items)) {
                $item = $items->firstWhere('id', $id);
            } else {
                $item = $this->model()->find($id);
            }

            if (! $item) {
                return response()->json(
                    [
                        'success' => false,
                        'errors' => [__($this->getTable().'.not_found')],
                    ]
                );
            }

            if (method_exists($this, 'afterReadOne')) {
                $this->afterReadOne($item, $request);
            }

            return response()->json(
                [
                    'success' => true,
                    'data' => ['item' => $item],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error caught in function CrudController.readOne: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    public function readAll(Request $request)
    {
        try {
            $user = $request->user();
            if (in_array('read_all', $this->restricted)) {
                if (! $user->hasPermission($this->getTable(), 'read') && ! $user->hasPermission($this->getTable(), 'read_own')) {
                    return response()->json(
                        [
                            'success' => false,
                            'errors' => [__('common.permission_denied')],
                        ]
                    );
                }
            }

            $items = [];

            $params = $this->getDatatableParams($request);

            $query = $this->getReadAllQuery()->dataTable($params);

            if ($request->input('per_page', 50) === 'all') {
                $items = $query->get();
            } else {
                $items = $query->paginate($request->input('per_page', 50));
            }

            if (method_exists($this, 'afterReadAll')) {
                $this->afterReadAll($items);
            }

            $items = collect(method_exists($items, 'items') ? $items->items() : $items);

            return response()->json(
                [
                    'success' => true,
                    'data' => [
                        'items' => $items,
                        'meta' => [
                            'current_page' => method_exists($items, 'currentPage') ? $items->currentPage() : 1,
                            'last_page' => method_exists($items, 'lastPage') ? $items->lastPage() : 1,
                            'total_items' => method_exists($items, 'total') ? $items->total() : $items->count(),
                        ],
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error caught in function CrudController.readAll: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    public function updateOne($id, Request $request)
    {
        try {
            return DB::transaction(
                function () use ($id, $request) {
                    if (in_array('update', $this->restricted)) {
                        $user = $request->user();
                        if (! $user->hasPermission($this->getTable(), 'update', $id)) {
                            return response()->json(
                                [
                                    'success' => false,
                                    'errors' => [__('common.permission_denied')],
                                ]
                            );
                        }
                    }

                    $model = app($this->getModelClass());
                    $customValidationMsgs = method_exists($model, 'validationMessages') ? $model->validationMessages() : [];
                    $validated = $request->validate(app($this->getModelClass())->rules(), $customValidationMsgs);

                    $model = $this->model()->find($id);

                    if (! $model) {
                        return response()->json(
                            [
                                'success' => false,
                                'errors' => [__($this->getTable().'.not_found')],
                            ]
                        );
                    }

                    $model->update($validated);

                    if (method_exists($this, 'afterUpdateOne')) {
                        $this->afterUpdateOne($model, $request);
                    }

                    return response()->json(
                        [
                            'success' => true,
                            'data' => ['item' => $model],
                            'validated' => $validated,
                            'message' => __($this->getTable().'.updated'),
                        ]
                    );
                }
            );
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => Arr::flatten($e->errors())]);
        } catch (\Exception $e) {
            Log::error('Error caught in function CrudController.updateOne: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    public function patchOne($id, Request $request)
    {
        try {
            return DB::transaction(
                function () use ($id, $request) {
                    if (in_array('update', $this->restricted)) {
                        $user = $request->user();
                        if (! $user->hasPermission($this->getTable(), 'update', $id)) {
                            return response()->json(
                                [
                                    'success' => false,
                                    'errors' => [__('common.permission_denied')],
                                ]
                            );
                        }
                    }
                    $model = $this->model()->find($id);

                    if (! $model) {
                        return response()->json(
                            [
                                'success' => false,
                                'errors' => [__($this->getTable().'.not_found')],
                            ]
                        );
                    }

                    $rules = app($this->getModelClass())->rules($id);
                    $fields = array_keys($request->all());
                    $validated = $request->validate(Arr::only($rules, $fields));

                    $model->update($validated);

                    if (method_exists($this, 'afterPatchOne')) {
                        $this->afterPatchOne($model, $request);
                    }

                    return response()->json(
                        [
                            'success' => true,
                            'data' => ['item' => $model],
                            'validated' => $validated,
                            'message' => __($this->getTable().'.updated'),
                        ]
                    );
                }
            );
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => Arr::flatten($e->errors())]);
        } catch (\Exception $e) {
            Log::error('Error caught in function CrudController.patchOne: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    public function deleteOne($id, Request $request)
    {
        try {
            return DB::transaction(
                function () use ($id, $request) {
                    if (in_array('delete', $this->restricted)) {
                        $user = $request->user();
                        if (! $user->hasPermission($this->getTable(), 'delete', $id)) {
                            return response()->json(
                                [
                                    'success' => false,
                                    'errors' => [__('common.permission_denied')],
                                ]
                            );
                        }
                    }

                    $model = $this->model()->find($id);

                    if (! $model) {
                        return response()->json(
                            [
                                'success' => false,
                                'errors' => [__($this->getTable().'.not_found')],
                            ]
                        );
                    }

                    if (method_exists($this, 'beforeDeleteOne')) {
                        $this->beforeDeleteOne($model, $request);
                    }

                    $model->delete();

                    if (method_exists($this, 'afterDeleteOne')) {
                        $this->afterDeleteOne($model, $request);
                    }

                    return response()->json(
                        [
                            'success' => true,
                            'message' => __($this->getTable().'.deleted'),
                        ]
                    );
                }
            );
        } catch (\Exception $e) {
            Log::error('Error caught in function CrudController.deleteOne: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }
}
