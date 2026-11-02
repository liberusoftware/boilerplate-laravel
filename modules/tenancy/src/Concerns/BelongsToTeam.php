<?php

declare(strict_types=1);

namespace Liberu\Foundation\Tenancy\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Jetstream\Jetstream;

trait BelongsToTeam
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel());
    }
}
