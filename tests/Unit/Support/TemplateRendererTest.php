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
