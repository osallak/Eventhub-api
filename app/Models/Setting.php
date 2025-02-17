<?php

namespace App\Models;

class Setting extends BaseModel
{
    public static $cacheKey = 'settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public function rules($id = null)
    {
        $id = $id ?? request()->route('id');

        return [
            'key' => 'required|string',
            'value' => 'required|string',
        ];
    }
}
