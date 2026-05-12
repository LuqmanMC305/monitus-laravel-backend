<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityBroadcast extends Model
{
    // Use the primary key defined in your migration
    protected $primaryKey = 'community_broadcast_id';

    protected $fillable = [
        'alert_id',
        'community_id',
        'community_status',
        'telegram_message_id',
        'error_log'
    ];

    // Relationship: A broadcast belongs to one alert
    public function alert()
    {
        return $this->belongsTo(Alert::class, 'alert_id', 'alert_id');
    }

    // Relationship: A broadcast belongs to one community
    public function community()
    {
        return $this->belongsTo(Community::class, 'community_id', 'community_id');
    }
}
