<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    protected $table = 'incident_reports';

    protected $fillable = [
        'app_user_id',
        'title',
        'incident_description',
        'latitude',
        'longitude',
        'area_type',
        'radius',
        'image_path',
        'llm_spam_score',
        'status',
    ];

    // Type Casting (Converts database text/strings into numbers automatically)
    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
        'radius' => 'integer',
        'llm_spam_score' => 'double',
    ];

    // Relationship: Every report belongs to a specific Mobile User account
    public function appUser()
    {
        // Links app_user_id foreign key inside incident_reports to app_user_id primary key in app_users
        return $this->belongsTo(AppUser::class, 'app_user_id', 'app_user_id');
    }
}
