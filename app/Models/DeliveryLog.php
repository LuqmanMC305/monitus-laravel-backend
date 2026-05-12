<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryLog extends Model
{
    // Define the table name 
    protected $table = 'delivery_logs';

    // Define the custom Primary Key 
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'alert_id',
        'mobile_user_id',
        'is_success',
        'delivered_at'
    ];
}
