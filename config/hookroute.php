<?php

return [
    'repository_url' => env('HOOKROUTE_REPOSITORY_URL'),
    'max_payload_bytes' => (int) env('HOOKROUTE_MAX_PAYLOAD_BYTES', 1_048_576),
    'allow_private_destinations' => (bool) env('HOOKROUTE_ALLOW_PRIVATE_DESTINATIONS', false),
    'delivery_timeout_seconds' => (int) env('HOOKROUTE_DELIVERY_TIMEOUT', 10),
    'delivery_processing_lease_seconds' => (int) env('HOOKROUTE_DELIVERY_PROCESSING_LEASE', 45),
    'response_excerpt_bytes' => 2_000,
    'max_response_bytes' => (int) env('HOOKROUTE_MAX_RESPONSE_BYTES', 65_536),
    'digest_max_events' => (int) env('HOOKROUTE_DIGEST_MAX_EVENTS', 100),
    'digest_event_preview_bytes' => (int) env('HOOKROUTE_DIGEST_EVENT_PREVIEW_BYTES', 4_000),
    'digest_processing_lease_seconds' => (int) env('HOOKROUTE_DIGEST_PROCESSING_LEASE', 75),
    'allow_public_registration' => (bool) env('HOOKROUTE_ALLOW_PUBLIC_REGISTRATION', false),
];
