<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketTemplate extends Model
{
    protected $fillable = [
        'name', 'category_id', 'subcategory_id', 'priority',
        'description_template', 'created_by', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function category() { return $this->belongsTo(Category::class); }
    public function subcategory() { return $this->belongsTo(Category::class, 'subcategory_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
