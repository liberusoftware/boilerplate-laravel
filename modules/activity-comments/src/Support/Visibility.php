<?php

namespace Liberu\Foundation\ActivityComments\Support;

enum Visibility: string
{
    case Public = 'public';
    case Members = 'members';
    case Internal = 'internal';
    case Private = 'private';

    public function visible(bool $member, bool $staff, bool $owner): bool
    {
        return match ($this) {
            self::Public => true,self::Members => $member || $staff,self::Internal => $staff,self::Private => $owner || $staff
        };
    }
}
