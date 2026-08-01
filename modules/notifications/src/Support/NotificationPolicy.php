<?php

namespace Liberu\Foundation\Notifications\Support;

use DateTimeImmutable;

final class NotificationPolicy
{
    /** @param list<string> $allowedChannels @return list<string> */
    public function channels(array $requested, array $allowedChannels, bool $operational = false): array
    {
        $channels = array_values(array_intersect($requested, $allowedChannels));

        return $channels === [] && $operational ? ['database'] : $channels;
    }

    public function isQuiet(DateTimeImmutable $localTime, ?string $starts, ?string $ends): bool
    {
        if ($starts === null || $ends === null) {
            return false;
        }
        $time = $localTime->format('H:i');

        return $starts <= $ends ? $time >= $starts && $time < $ends : $time >= $starts || $time < $ends;
    }
}
