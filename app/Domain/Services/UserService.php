<?php

namespace App\Domain\Services;

use App\Models\User;
use Illuminate\Support\Str;

class UserService
{
    /**
     * @throws \Throwable
     */
    public static function create(array $payload, bool $generateDefaultEmail = true, bool $isMaster = false): User
    {
        if (!array_key_exists('email', $payload) && $generateDefaultEmail) {
            $payload['email'] = Str::slug($payload['code']) . '.' . uniqid('', false) . '@local';
        }

        $model = new User($payload);
        $model->is_master = $isMaster;
        $model->saveOrFail();

        return $model;
    }
}
