<?php

/**
 * @property int $id
 * @property string $title
 * @property float $latitude
 * @property float $longitude
 * @property float $radius
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $primaryKey = 'alert_id';

    // Acts as a security guard to protect its attributes
    protected $fillable = [
        'admin_id', 'title', 'instruction', 'status', 
        'severity', 'latitude', 'longitude', 'radius'
    ];

    /**
     * Get the Admin that send the alert.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }


    /*  Many-to-Many relationship via junction table 
        Looks at alert_id and hops to deliver_logs table)
        Find all associated mobile_user_id
    */
    public function mobileUsers()
    {
        return $this->belongsToMany(MobileUser::class, 'delivery_logs', 'alert_id', 'mobile_user_id')
                    ->withPivot('is_success', 'delivered_at');
    }

    public function communityBroadcasts()
    {
        return $this->hasMany(CommunityBroadcast::class, 'alert_id', 'alert_id');
    }
}
