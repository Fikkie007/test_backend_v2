<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['title', 'price', 'description', 'category'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function files()
    {
        return $this->hasMany(File::class, 'parent_id')->where('parent_table', 'products');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
