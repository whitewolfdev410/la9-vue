<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerCustomer extends Model
{
    use HasFactory;
       
    protected $fillable = [
        'order_number','dealer_name', 'dealer_surname', 'dealer_middlename', 'customer_email', 'customer_name', 'customer_surname', 'customer_middle_name', 'customer_facility', 'customer_department', 'customer_city', 'customer_prefecture', 'customer_country', 'user_id', 'app_id', 'cat_id'
    ];
}
