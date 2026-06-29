<?php

// Jetstream's Livewire-stack Blade components must be published into
// resources/views/components, otherwise every profile/team/API view that
// references them throws "Unable to locate a class or view for component".

it('has the jetstream blade components published', function (string $component) {
    expect(view()->exists('components.'.$component))->toBeTrue();
})->with([
    'input-error',
    'action-section',
    'form-section',
    'section-title',
    'action-message',
    'confirmation-modal',
    'dialog-modal',
    'modal',
    'danger-button',
    'secondary-button',
    'checkbox',
    'confirms-password',
    'dropdown',
    'dropdown-link',
    'nav-link',
    'responsive-nav-link',
    'banner',
    'switchable-team',
    // Socialstream components used by the connected-accounts views.
    'action-link',
    'connected-account',
    'socialstream',
    'socialstream-icons.provider-icon',
]);
