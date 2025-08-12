<?php

namespace App\Domain\Services;

use App\Models\Message;

class MessageService
{
    /**
     * @throws \Throwable
     */
    public static function create(array $payload): Message
    {
        $model = new Message($payload);
        $model->saveOrFail();

        return $model;
    }
}
