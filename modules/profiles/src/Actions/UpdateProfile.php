<?php

namespace Liberu\Foundation\Profiles\Actions;

use Illuminate\Database\Eloquent\Model;
use Liberu\Foundation\Profiles\Data\ProfileUpdate;

final class UpdateProfile
{
    public function handle(Model $profile, ProfileUpdate $update): Model
    {
        $profile->forceFill(array_filter([
            'name' => trim($update->name),
            'locale' => $update->locale,
            'timezone' => $update->timezone,
            'theme_preference' => $update->theme,
        ], fn ($value) => $value !== null))->save();

        return $profile->refresh();
    }
}
