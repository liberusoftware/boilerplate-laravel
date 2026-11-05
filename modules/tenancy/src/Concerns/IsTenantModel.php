<?php

namespace Liberu\Foundation\Tenancy\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Liberu\Foundation\Tenancy\TenantContext;

trait IsTenantModel
{
    use BelongsToTeam;

    protected static function bootIsTenantModel(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if ($teamId = TenantContext::currentId()) {
                $model = $builder->getModel();
                $builder->where($model->qualifyColumn('team_id'), $teamId);
            }
        });

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('team_id')) && $teamId = TenantContext::currentId()) {
                $model->setAttribute('team_id', $teamId);
            }
        });
    }

    public function scopeByTeam(Builder $query, ?int $teamId): void
    {
        if ($teamId) {
            $query->where($query->qualifyColumn('team_id'), (int) $teamId);
        }
    }

    public function belongsToTeam(?int $teamId): bool
    {
        return ! $teamId || $this->team_id === $teamId;
    }
}
