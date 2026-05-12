<?php

/**
 * @property int $app_user_id
 * @property string $app_user_name
 * @property string $app_user_email
 * @property string $app_user_password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AppUser extends Model
{
    protected $table = 'app_users';
    protected $primaryKey = 'app_user_id';

    use HasApiTokens, Notifiable;

    protected $fillable = [
    
        'app_user_name',
        'app_user_email',
        'app_user_password'
    ];

    public function communities()
    {
        // This links the two tables via the community_user pivot table
        return $this->belongsToMany(Community::class, 'community_user', 'mobile_user_id', 'community_id')
                    ->withPivot('status', 'role') // Allows you to access 'pending' or 'approved'
                    ->withTimestamps();
    }
}
