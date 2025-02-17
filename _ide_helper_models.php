<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */

namespace App\Models{
    /**
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTable(\Illuminate\Http\Request $request, bool $checkPermission = true)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableFilter($column, $operatorWithValue)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableSort(array $orderParam)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel query()
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel readPermission()
     */
    class BaseModel extends \Eloquent {}
}

namespace App\Models{
    /**
     * @property int $id
     * @property string $name
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
     * @property-read int|null $roles_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
     * @property-read int|null $users_count
     *
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTable(\Illuminate\Http\Request $request, bool $checkPermission = true)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableFilter($column, $operatorWithValue)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableSort(array $orderParam)
     * @method static \Illuminate\Database\Eloquent\Builder|Permission newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Permission newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Permission query()
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel readPermission()
     * @method static \Illuminate\Database\Eloquent\Builder|Permission whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Permission whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Permission whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Permission whereUpdatedAt($value)
     */
    class Permission extends \Eloquent {}
}

namespace App\Models{
    /**
     * @property int $id
     * @property \App\Enums\ROLE $name
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
     * @property-read int|null $permissions_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
     * @property-read int|null $users_count
     *
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTable(\Illuminate\Http\Request $request, bool $checkPermission = true)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableFilter($column, $operatorWithValue)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableSort(array $orderParam)
     * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Role query()
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel readPermission()
     * @method static \Illuminate\Database\Eloquent\Builder|Role whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Role whereUpdatedAt($value)
     */
    class Role extends \Eloquent {}
}

namespace App\Models{
    /**
     * @property int $id
     * @property string $key
     * @property string $value
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     *
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTable(\Illuminate\Http\Request $request, bool $checkPermission = true)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableFilter($column, $operatorWithValue)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableSort(array $orderParam)
     * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel readPermission()
     * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Setting whereKey($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Setting whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValue($value)
     */
    class Setting extends \Eloquent {}
}

namespace App\Models{
    /**
     * @property int $id
     * @property string|null $name
     * @property string $path
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read mixed $path_without_disk
     * @property-read mixed $url
     *
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTable(\Illuminate\Http\Request $request, bool $checkPermission = true)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableFilter($column, $operatorWithValue)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableSort(array $orderParam)
     * @method static \Illuminate\Database\Eloquent\Builder|Upload newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Upload newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Upload query()
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel readPermission()
     * @method static \Illuminate\Database\Eloquent\Builder|Upload whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Upload whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Upload whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Upload wherePath($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Upload whereUpdatedAt($value)
     */
    class Upload extends \Eloquent {}
}

namespace App\Models{
    /**
     * @property int $id
     * @property string $email
     * @property \Illuminate\Support\Carbon|null $email_verified_at
     * @property string $password
     * @property string|null $remember_token
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read mixed $permissions_names
     * @property-read mixed $roles_names
     * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
     * @property-read int|null $notifications_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
     * @property-read int|null $permissions_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
     * @property-read int|null $roles_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
     * @property-read int|null $tokens_count
     *
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTable(\Illuminate\Http\Request $request, bool $checkPermission = true)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableFilter($column, $operatorWithValue)
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel dataTableSort(array $orderParam)
     * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|User query()
     * @method static \Illuminate\Database\Eloquent\Builder|BaseModel readPermission()
     * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
     * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
     * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
     * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
     */
    class User extends \Eloquent implements \Illuminate\Contracts\Auth\Access\Authorizable, \Illuminate\Contracts\Auth\Authenticatable, \Illuminate\Contracts\Auth\CanResetPassword {}
}
