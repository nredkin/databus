<?php
use Illuminate\Support\Str;

// A paginator may be injected as view data (used by tests); otherwise load the latest page.
$messages ??= \App\Models\Message::query()
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

    .payload-cell {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .link-btn {
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        font: inherit;
        color: #1565c0;
        cursor: pointer;
    }

    .link-btn:hover,
    .link-btn:focus {
        text-decoration: underline;
    }

    body.modal-open {
        overflow: hidden;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-overlay[hidden] {
        display: none;
    }

    .modal-dialog {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 900px;
        max-height: 85vh;
        background-color: #ffffff;
        border-radius: 6px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 16px;
        border-bottom: 1px solid #dededf;
    }

    .modal-title {
        margin: 0;
        font-size: 16px;
    }

    .modal-body {
        flex: 1 1 auto;
        overflow: auto;
        padding: 16px;
    }

    .modal-body pre {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 13px;
        line-height: 1.45;
    }

    .modal-footer {
        padding: 12px 16px;
        border-top: 1px solid #dededf;
        text-align: right;
    }

    .btn {
        padding: 6px 14px;
        font: inherit;
        color: #000000;
        background-color: #f5f7f8;
        border: 1px solid #c5c9cc;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn:hover,
    .btn:focus {
        background-color: #e8ecef;
    }
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
            <td>
                @php
                    $originalCode = json_decode($item->payload, true)['_messageCode'] ?? null;
                @endphp
                {{ $originalCode ?? $item->message_code->title() }}
            </td>
            <td>{{ $item->error_message }}</td>
            <td>
                <div class="payload-cell">
                    <span>{{ Str::limit($item->payload, 300) }}</span>
                    <button
                        type="button"
                        class="link-btn js-view-payload"
                        data-payload="{{ $item->payload }}"
                        aria-haspopup="dialog"
                    >View</button>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div>
        {{ $messages->links() }}
    </div>
</div>
    <div class="modal-overlay" id="payload-modal" hidden>
        <div
            class="modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="payload-modal-title"
        >
            <div class="modal-header">
                <h3 class="modal-title" id="payload-modal-title">Payload</h3>
                <button type="button" class="btn js-close-modal">Close</button>
            </div>
            <div class="modal-body">
                <pre><code id="payload-modal-content"></code></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn js-close-modal">Close</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var overlay = document.getElementById('payload-modal');
            var content = document.getElementById('payload-modal-content');
            var lastTrigger = null;

            if (!overlay || !content) {
                return;
            }

            function formatPayload(raw) {
                if (raw === null || raw === undefined || raw === '') {
                    return '(empty)';
                }

                try {
                    return JSON.stringify(JSON.parse(raw), null, 2);
                } catch (e) {
                    return raw;
                }
            }

            function openModal(raw, trigger) {
                content.textContent = formatPayload(raw);
                lastTrigger = trigger || null;
                overlay.hidden = false;
                document.body.classList.add('modal-open');

                var closeButton = overlay.querySelector('.js-close-modal');
                if (closeButton) {
                    closeButton.focus();
                }
            }

            function closeModal() {
                overlay.hidden = true;
                content.textContent = '';
                document.body.classList.remove('modal-open');

                if (lastTrigger && document.contains(lastTrigger)) {
                    lastTrigger.focus();
                }
                lastTrigger = null;
            }

            document.addEventListener('click', function (event) {
                var trigger = event.target.closest('.js-view-payload');

                if (trigger) {
                    event.preventDefault();
                    openModal(trigger.getAttribute('data-payload'), trigger);
                    return;
                }

                if (event.target.closest('.js-close-modal')) {
                    closeModal();
                    return;
                }

                // Clicking the backdrop (but not the dialog itself) closes the modal.
                if (event.target === overlay) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !overlay.hidden) {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>
