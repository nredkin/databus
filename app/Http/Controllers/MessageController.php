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
use Illuminate\Validation\Rule;

class MessageController extends ApiController
{
    private const int FETCH_MESSAGES_LIMIT = 50;

    public function index(Request $request)
    {
        $limit = max(0, min(100, $request->header('X-Limit', self::FETCH_MESSAGES_LIMIT)));
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
            // ->where('messages.status', MessageStatus::Awaiting->value)
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
            ])->get();

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
//                'sender' => ['sometimes', 'string', 'max:32', 'exists:users,code'],
                'recipient' => ['sometimes', 'string', 'max:32', 'exists:users,code'],
                'messageCode' => ['sometimes', Rule::enum(MessageCode::class)],
                'messageId' =>  ['sometimes', 'string', 'max:64', 'unique:messages,message_id'],
            ]);
//            $sender_id = !empty($headers['sender']) ? User::getIdByCode($headers['sender']) : null;
            $recipient_id = !empty($headers['recipient']) ? User::getIdByCode($headers['recipient']) : null;
            $message = MessageService::create([
//                'sender_id' => $sender_id ?? Auth::id(),
                'sender_id' => Auth::id(),
                'recipient_id' => $recipient_id,
                'message_id' => $headers['messageId'],
                'message_code' => $headers['messageCode'],
                'payload' => $request->getContent(),
            ]);

            return $message->id;
        });
    }
}
