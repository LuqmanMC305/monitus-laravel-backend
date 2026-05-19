<?php

/**
 * @property string $telegram_group_id
 * @property string $community_name
 * @property int $community_id
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    protected $primaryKey = 'community_id';

    protected $fillable = [
        'community_name',
        'telegram_group_id',
        'community_description',
        'telegram_link',
        'community_location',
    ];

    public function mobileUsers()
    {
        // Target: MobileUser, Pivot: community_user, Local FK: community_id, Remote FK: mobile_user_id
        return $this->belongsToMany(MobileUser::class, 'community_user', 'community_id', 'mobile_user_id')
                ->using(\App\Models\CommunityUser::class)
                ->withPivot('status','role')
                ->withTimestamps();
    }
}
