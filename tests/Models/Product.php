<?php

namespace Javaabu\QueryBuilder\Tests\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Javaabu\QueryBuilder\Tests\Factories\ProductFactory;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', '%'.$search.'%');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function getFormattedNameAttribute(): string
    {
        return 'Formatted ' . $this->name;
    }

    public function scopeWithRating($query, $rating)
    {
        if (! $query->getQuery()->columns) {
            $query->select('*');
        }

        $rating = (int) $rating;

        return $query->selectRaw('(' .$rating . ' + id) as rating');
    }
}
