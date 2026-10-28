<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToStore;

    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'store_id', 'action', 'auditable_type', 'auditable_id', 'description', 'meta', 'ip'];

    protected $casts = ['meta' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
