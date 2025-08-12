<?php

namespace App\Models;

use App\Domain\Enums\MessageCode;
use App\Domain\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sender_id
 * @property int $recipient_id
 * @property string $created_at
 * @property string|null $processed_at
 * @property string $message_id
 * @property string $message_code
 * @property string|null $error_message
 * @property int $status
 * @property string|null $payload
 */
class Message extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'message_id',
        'message_code',
        'payload',
    ];

    protected $visible = [
        'id',
        'sender_id',
        'recipient_id',
        'created_at',
        'processed_at',
        'message_id',
        'message_code',
        'error_message',
        'status',
        'payload',
    ];

    protected $casts = [
        'message_code' => MessageCode::class,
    ];

    public static function markMessages(array $ids, int $messageStatus, mixed $errorMessage = false): int
    {
        if (empty($ids)) {
            return 0;
        }

        $processedAt = now()->format('Y-m-d H:i:s');

        $payload = [
            'processed_at' => $processedAt,
            'status' => $messageStatus,
        ];

        if ($errorMessage !== false) {
            $payload['error_message'] = $errorMessage;
        }

        return self::query()
            ->whereIn('id', $ids)
            ->update($payload);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
