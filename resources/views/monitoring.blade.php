<?php
use Illuminate\Support\Str;

$messages = \App\Models\Message::query()
    ->latest()
    ->paginate(50);
?>
<html>
<body>
<style>
    .table_component {
        overflow: auto;
        width: 100%;
    }

    .table_component table {
        border: 1px solid #dededf;
        height: 100%;
        width: 100%;
        border-collapse: collapse;
        border-spacing: 1px;
        text-align: left;
    }

    .table_component caption {
        caption-side: top;
        text-align: left;
    }

    .table_component th {
        border: 1px solid #dededf;
        background-color: #eceff1;
        color: #000000;
        padding: 5px;
        text-align: center;
    }

    .table_component td {
        border: 1px solid #dededf;
        background-color: #ffffff;
        color: #000000;
        padding: 5px;
        word-break: break-all;
    }

    table tbody > tr > td:nth-child(1) { width: 2%; text-align: center; }
    table tbody > tr > td:nth-child(2) { width: 10%; text-align: center; }
    table tbody > tr > td:nth-child(3) { width: 10%; text-align: center; }
    table tbody > tr > td:nth-child(4) { width: 5%; text-align: center; }
    table tbody > tr > td:nth-child(5) { width: 7%; text-align: center; }
    table tbody > tr > td:nth-child(6) { width: 7%; text-align: center; }
    table tbody > tr > td:nth-child(7) { width: 10%; text-align: center; }
    table tbody > tr > td:nth-child(8) { width: 10%; text-align: center; }
    table tbody > tr > td:nth-child(9) { width: 20%; }
    table tbody > tr > td:nth-child(10) { width: 30%; max-width: 300px; }
</style>
<div class="table_component" role="region" tabindex="0">
    <table>
        <h2>Сообщения</h2>
        <thead>
        <tr>
            <th>Ser.ID</th>
            <th>Sender</th>
            <th>Recipient</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Processed At</th>
            <th>Message ID</th>
            <th>Message Code</th>
            <th>Error</th>
            <th>Payload</th>
        </tr>
        </thead>
        <tbody>
        @foreach($messages as $item)
        <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->sender?->code ?? '?' }}</td>
            <td>{{ $item->recipient?->code ?? '?' }}</td>
            <td>{{ $item->status }}</td>
            <td>{{ $item->created_at }}</td>
            <td>{{ $item->processed_at }}</td>
            <td>{{ $item->message_id }}</td>
            <td>{{ $item->message_code->title() }}</td>
            <td>{{ $item->error_message }}</td>
            <td>{{ Str::limit($item->payload, 300) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div>
        {{ $messages->links() }}
    </div>
</div>
</body>
</html>
