<?php

namespace App\Http\Controllers;

use App\Domain\Enums\MessageCode;
use App\Domain\Enums\MessageStatus;
use App\Domain\Services\MessageService;
use App\Http\Middleware\ParseHeaders;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends ApiController
{
    private const int FETCH_MESSAGES_LIMIT = 50;

    public function index(Request $request)
    {
        $limit = max(1, min(1000, $request->header('X-Limit', self::FETCH_MESSAGES_LIMIT)));
        $codes = array_filter(explode(',', base64_decode($request->header('X-Codes', ''))));

        $user = $this->user();
        if ($user->is_master && !empty($codes)) {
            $usersIdsQuery = User::query()->select('id')->whereIn('code', $codes);
            $query = Message::query()->whereIn('recipient_id', $usersIdsQuery);
        } else {
            $query = $this->user()->incomeMessages();
        }

        $query = $query
            ->join('users as susers', 'susers.id', '=', 'messages.sender_id')
            ->join('users as rusers', 'rusers.id', '=', 'messages.recipient_id')
            ->where('messages.status', MessageStatus::Awaiting->value)
            ->orderBy('messages.id')
            ->limit($limit)
            ->toBase();

        $ids = (clone $query)->pluck('messages.id')->toArray();
        request()->attributes->set('_messageIds', $ids);

        $items = $query
            ->select([
                'susers.code as Sender',
                'rusers.code as Recipient',
                'message_code as MessageCode',
                'message_id as MessageId',
                'payload as Data',
            ])->get()
            ->map(fn ($item) => $this->restoreMessageCodeInResponse($item));

        Message::markMessages(
            $request->attributes->get('_messageIds'),
            MessageStatus::Failed->value,
            'Sending...'
        );

        return response()->json($items);
    }

    public function store(Request $request, ParseHeaders $parseHeaders)
    {
        return $this->withErrorControl(function () use ($request, $parseHeaders) {
            $headers = Validator::validate($parseHeaders->getHeaders(), [
                'sender' => ['nullable', 'string', 'max:32', 'exists:users,code'],
                'recipient' => ['nullable', 'string', 'max:32', 'exists:users,code'],
                'recipients' => ['nullable', 'string', 'max:1000'],
                // TEMP: accept any MessageCode without enum validation
                'messageCode' => ['required', 'string', 'max:64'],
                'messageId' => ['nullable', 'string', 'max:64', 'unique:messages,message_id', 'required_with:recipient'],
            ]);

            $senderId = empty($headers['sender']) ? null : User::getIdByCode($headers['sender']);
            $payloadData = json_decode($request->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR) ?? [];
            $messageCode = $this->normalizeMessageCode($headers['messageCode'], $payloadData);
            $payload = json_encode($payloadData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            if (!empty($headers['recipients'])) {
                $recipientIds = array_filter(array_map('trim',explode(',', $headers['recipients'])));

                $userIds = empty($recipientIds) ? [] : User::query()
                    ->whereIn('code', $recipientIds)
                    ->pluck('id')
                    ->toArray();

                return empty($userIds) ? null : MessageService::createMany([
                    'sender_id' => $senderId ?? Auth::id(),
                    'message_code' => $messageCode,
                    'payload' => $payload,
                ], $userIds);
            } else {
                $recipientId = empty($headers['recipient']) ? null : User::getIdByCode($headers['recipient']);

                return empty($recipientId) ? null : MessageService::create([
                    'sender_id' => $senderId ?? Auth::id(),
                    'recipient_id' => $recipientId,
                    'message_id' => $headers['messageId'],
                    'message_code' => $messageCode,
                    'payload' => $payload,
                ]);
            }
        });
    }

    // TEMP: store unknown codes as None, keep original in payload
    private function normalizeMessageCode(string $messageCode, array &$payload): string
    {
        if (in_array($messageCode, MessageCode::names(), true)) {
            return $messageCode;
        }

        $payload['_messageCode'] = $messageCode;

        return MessageCode::None->name;
    }

    // TEMP: restore original MessageCode for consumers
    private function restoreMessageCodeInResponse(object $item): object
    {
        $payload = json_decode($item->Data ?? '', true);

        if (!is_array($payload) || !isset($payload['_messageCode'])) {
            return $item;
        }

        $item->MessageCode = $payload['_messageCode'];
        unset($payload['_messageCode']);
        $item->Data = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return $item;
    }
}
