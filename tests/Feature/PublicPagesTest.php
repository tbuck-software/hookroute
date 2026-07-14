<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the public landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('canRegister')
            ->has('repositoryUrl'));
});

it('renders the public legal pages', function (string $routeName, string $component) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->has('repositoryUrl'));
})->with([
    ['imprint', 'Legal/Imprint'],
    ['privacy', 'Legal/Privacy'],
]);
