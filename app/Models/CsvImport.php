<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsvImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'status',
        'error_message',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'imported_rows' => 'integer',
        'failed_rows' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
