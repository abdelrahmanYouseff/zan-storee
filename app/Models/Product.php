<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'main_image',
        'secondary_images',
        'name',
        'description',
        'features',
        'colors',
        'quantity',
        'price_before',
        'price_after',
        'is_active',
        'paypal_full_payment_url',
        'paypal_cod_payment_url',
    ];

    protected $casts = [
        'secondary_images' => 'array',
        'features' => 'array',
        'colors' => 'array',
        'quantity' => 'integer',
        'price_before' => 'decimal:2',
        'price_after' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->price_before > 0) {
            return round((($this->price_before - $this->price_after) / $this->price_before) * 100);
        }
        return 0;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock()
    {
        return $this->quantity > 0;
    }

    /**
     * Scope for active products only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
