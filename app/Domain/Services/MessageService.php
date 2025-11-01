<?php

namespace App\Domain\Services;

use App\Models\Message;

class MessageService
{
    /**
     * @throws \Throwable
     */
    public static function create(array $payload, bool $returnId = true): string|Message
    {
        $model = new Message($payload);
        $model->saveOrFail();

        return $returnId ? $model->id : $model;
    }

    public static function createMany(array $defaultPayload, array $recipientIds, bool $returnId = true): string|array
    {
        $result = [];

        foreach ($recipientIds as $recipientId) {
            $payload = $defaultPayload;
            $payload['recipient_id'] = $recipientId;
            $model = new Message($payload);
            $model->saveOrFail();

            $result[] = $returnId ? $model->id : $model;
        }

        return $result;
    }
}
