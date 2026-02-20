<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'cnic',
        'address',
        'status',
        'nic_front',
        'nic_back',
    ];
}
