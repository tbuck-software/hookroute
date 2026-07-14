<!doctype html>
<html lang="en">
<body style="margin:0;background:#f3f0e8;color:#17211b;font-family:Arial,sans-serif">
<div style="max-width:720px;margin:0 auto;padding:32px 20px">
    <div style="background:#d7ff64;padding:22px 24px;border:1px solid #17211b">
        <div style="font-size:12px;letter-spacing:.14em;text-transform:uppercase">{{ $run->destination->project->name }}</div>
        <h1 style="font-size:28px;margin:6px 0 0">{{ $run->event_count }} events in this digest</h1>
        @if ($run->truncated)
            <p style="margin:8px 0 0;font-size:12px">Showing the first {{ $run->event_count }} of {{ $run->total_event_count }} events in this window.</p>
        @endif
    </div>
    @foreach ($run->events() as $event)
        <div style="background:#fff;padding:20px 24px;border:1px solid #d8d4c9;border-top:0">
            <div style="font-size:12px;color:#667069">{{ $event->received_at->timezone($run->destination->config['timezone'] ?? 'UTC')->format('H:i:s') }} · {{ $event->source->name }}</div>
            <pre style="white-space:pre-wrap;word-break:break-word;font:13px/1.55 monospace">{{ \Illuminate\Support\Str::limit(json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), config('hookroute.digest_event_preview_bytes'), '…') }}</pre>
            <div style="font-size:11px;color:#8a938d">{{ $event->public_id }}</div>
        </div>
    @endforeach
</div>
</body>
</html>
