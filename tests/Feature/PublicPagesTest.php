<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the public landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<html lang="de">', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('canRegister')
            ->where('locale', 'de')
            ->has('repositoryUrl'));
});

it('renders the english landing page', function () {
    $this->get(route('home.en'))
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('canRegister')
            ->where('locale', 'en')
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
