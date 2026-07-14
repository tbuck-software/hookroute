<!doctype html>
<html lang="en">
<body style="margin:0;background:#f3f0e8;color:#17211b;font-family:Arial,sans-serif">
<div style="max-width:680px;margin:0 auto;padding:32px 20px">
    <div style="background:#17211b;color:#fff;padding:16px 20px;font-size:13px;letter-spacing:.12em;text-transform:uppercase">Hookroute · {{ $event->source->name }}</div>
    <div style="background:#fff;padding:28px;border:1px solid #d8d4c9">
        <div style="white-space:pre-wrap;line-height:1.6">{{ $bodyText }}</div>
        <p style="margin-top:24px;color:#667069;font-size:12px">Event {{ $event->public_id }} · {{ $event->received_at->toIso8601String() }}</p>
    </div>
</div>
</body>
</html>
