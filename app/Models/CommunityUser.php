<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CommunityUser extends Pivot
{
    protected $table = 'community_user';
    public $status;
    public $role;
}
