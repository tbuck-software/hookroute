<?php

use App\Services\EventMatcher;

it('matches supported payload filters', function () {
    $matcher = new EventMatcher;
    $payload = ['status' => 'ready', 'tags' => ['paid', 'priority'], 'amount' => 42];

    expect($matcher->matches($payload, [
        ['field' => 'status', 'operator' => 'equals', 'value' => 'ready'],
        ['field' => 'tags', 'operator' => 'contains', 'value' => 'paid'],
        ['field' => 'missing', 'operator' => 'not_exists', 'value' => null],
    ]))->toBeTrue();
});
