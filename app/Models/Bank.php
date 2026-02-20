<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_title',
        'account_number',
        'branch_code',
        'branch_name',
        'iban',
        'status'
    ];
}
