<?php

namespace Liberu\Foundation\UsersRoles\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Liberu\Foundation\UsersRoles\Concerns\HasUserRoles;

class User extends Authenticatable
{
    use HasUserRoles;
}
