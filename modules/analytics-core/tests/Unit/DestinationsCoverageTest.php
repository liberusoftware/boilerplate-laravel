<?php

use Liberu\Analytics\Contracts\AnalyticsDestination;
use Liberu\Analytics\Core\Support\DestinationRegistry;

it('rejects duplicate and unknown analytics destinations', function () {
    $destination = Mockery::mock(AnalyticsDestination::class);
    $destination->shouldReceive('name')->andReturn('test');
    $registry = new DestinationRegistry();
    $registry->register($destination);

    expect($registry->get('test'))->toBe($destination)
        ->and($registry->all())->toBe(['test' => $destination])
        ->and(fn () => $registry->register($destination))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $registry->get('missing'))->toThrow(InvalidArgumentException::class);
});
