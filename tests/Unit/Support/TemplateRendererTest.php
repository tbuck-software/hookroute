<?php

use App\Services\TemplateRenderer;

it('renders nested values while preserving json value types', function () {
    $renderer = new TemplateRenderer;
    $rendered = $renderer->render('{"id": {{ payload.id }}, "active": {{ payload.active }}, "label": "{{ project.name }}"}', [
        'payload' => ['id' => 42, 'active' => true],
        'project' => ['name' => 'Operations'],
    ]);

    expect(json_decode($rendered, true))->toBe([
        'id' => 42,
        'active' => true,
        'label' => 'Operations',
    ]);
});

it('keeps scalar values raw in plain text mode', function () {
    $renderer = new TemplateRenderer;
    $rendered = $renderer->render('He said "{{ payload.quote }}" on {{ payload.day }}.', [
        'payload' => ['quote' => 'stop "all" processing', 'day' => "Mon\Tue"],
    ], jsonSafe: false);

    expect($rendered)->toBe('He said "stop "all" processing" on Mon\Tue.');
});

it('escapes scalar values for json bodies by default', function () {
    $renderer = new TemplateRenderer;
    $rendered = $renderer->render('{"quote": "{{ payload.quote }}"}', [
        'payload' => ['quote' => 'say "hi"'],
    ]);

    expect(json_decode($rendered, true))->toBe(['quote' => 'say "hi"']);
});
