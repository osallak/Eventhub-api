<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends CrudController
{
    protected $table = 'uploads';

    protected $modelClass = Upload::class;

    protected $rules = [
        'name' => 'nullable|string',
        'file' => 'required|file',
    ];

    protected $restricted = ['read_one', 'read_all', 'update', 'delete'];

    protected function getTable()
    {
        return $this->table;
    }

    protected function getModelClass()
    {
        return $this->modelClass;
    }

    public function createOne(Request $request)
    {
        try {
            $request->validate($this->rules);
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'-'.Str::uuid().'.'.$extension;
            Storage::disk('cloud')->put($filename, $file->get());
            $path = "/cloud/$filename";
            $request->merge(['path' => $path]);

            return parent::createOne($request);
        } catch (\Exception $e) {
            Log::error('Error caught in function UploadController.createOne : '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    public function updateOne($id, Request $request)
    {
        try {
            $request->validate($this->rules);

            $currentPath = $this->model()->find($id)->path;
            if ($currentPath) {
                $currentPath = str_replace('/cloud', '', $currentPath);
                Storage::disk('cloud')->delete($currentPath);
            }

            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'-'.Str::uuid().'.'.$extension;
            Storage::disk('cloud')->put($filename, $file->get());
            $path = "/cloud/$filename";
            $request->merge(['path' => $path]);

            return parent::updateOne($id, $request);
        } catch (\Exception $e) {
            Log::error('Error caught in function UploadController.updateOne : '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    public function deleteMulti(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPermission($this->table, 'delete')) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => [__('common.permission_denied')],
                ]
            );
        }

        $request->validate(
            [
                'ids' => 'required|array',
                'ids.*' => 'exists:uploads,id',
            ]
        );

        $hasEachItemPermission = collect($request->ids)->every(
            function ($id) use ($user) {
                return $user->hasPermission($this->table, 'delete', $id);
            }
        );

        if (! $hasEachItemPermission) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => [__('common.permission_denied')],
                ]
            );
        }

        try {
            Upload::whereIn('id', $request->ids)->each(
                function ($upload) {
                    $upload->delete();
                }
            );

            return response()->json(
                [
                    'success' => true,
                    'message' => __("$this->table.deleted"),
                ]
            );
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            Log::error($th->getTraceAsString());

            return response()->json(
                [
                    'success' => false,
                    'message' => __("$this->table.unexpected_error"),
                ]
            );
        }
    }

    public function readImage($id)
    {
        try {
            $upload = $this->model()->find($id);
            if (! $upload) {
                return response()->json(
                    [
                        'success' => false,
                        'errors' => [__('uploads.not_found')],
                    ], 404
                );
            }
            $path = str_replace('/cloud', '', $upload->path);
            if (! Storage::disk('cloud')->exists($path)) {
                return response()->json(
                    [
                        'success' => false,
                        'errors' => [__('uploads.not_found')],
                    ], 404
                );
            }

            return Storage::disk('cloud')->response($path);
        } catch (\Exception $e) {
            Log::error('Error caught in function UploadController.readImage : '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }
}
