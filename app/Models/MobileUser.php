<?php


namespace App\Models;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\DeliveryLog;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class MobileUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Use snake_case as per Laravel conventions.
     */
    protected $table = 'mobile_users';

    /**
     * Primary Key of this table.
     *  */    
    protected $primaryKey = 'mobile_user_id';

    /**
     * Mass assignable attributes 
     * Prevents MassAssignmentExceptions during registration
     */
    protected $fillable = [
        'app_user_id',
        'device_id',
        'fcm_token',
        'last_location',
        'last_location_at',
        'telegram_chat_id',
        'is_telegram_verified',
        ];

    /**
     * The attributes that should be cast to native types.
     * 'last_location_at' is treated as a Carbon instance automatically.
     */
    protected $casts = [
        'last_location_at' => 'datetime',
    ];
  
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * One-to-many relationship ("One Mobile User can be found in many different rows of delivery logs.)
     */

    public function deliveryLogs()
    {
        return $this->hasMany(DeliveryLog::class, 'mobile_user_id', 'mobile_user_id');
    }

    public function alerts(): BelongsToMany
    {
        // Path: MobileUser -> delivery_logs -> Alert
        return $this->belongsToMany(
            Alert::class, 
            'delivery_logs',    // The junction table
            'mobile_user_id',   // FK on junction table pointing to this model
            'alert_id'          // FK on junction table pointing to Alert
        )->withPivot('is_success', 'delivered_at');
    }

    public function communities()
    {
        return $this->belongsToMany(Community::class, 'community_user', 'mobile_user_id', 'community_id')
                    ->withPivot('status','role')
                    ->withTimestamps();
        
    }

    public function appUser() {
        return $this->belongsTo(AppUser::class, 'app_user_id', 'app_user_id');
    }


}
