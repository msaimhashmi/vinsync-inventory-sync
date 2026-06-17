<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Vehicle extends Model
{
    protected $fillable = [
        'vin', 'stock_number', 'condition', 'make', 'model', 'year', 'trim',
        'body_style', 'engine', 'transmission', 'drivetrain', 'fuel_type',
        'mpg_city', 'mpg_hwy', 'msrp', 'sale_price', 'mileage',
        'exterior_color', 'exterior_color_code', 'interior_color', 'interior_color_code',
        'status', 'dealer_name', 'dealer_city', 'dealer_state',
        'detail_url', 'features', 'images', 'window_sticker_url',
    ];

    protected $casts = [
        'features' => 'array',
        'images'   => 'array',
        'msrp'     => 'float',
        'sale_price' => 'float',
        'mileage'  => 'integer',
        'mpg_city' => 'integer',
        'mpg_hwy'  => 'integer',
        'year'     => 'integer',
    ];

    /** Display price: prefer sale_price, fall back to msrp. */
    public function getDisplayPriceAttribute(): ?float
    {
        return $this->sale_price ?? $this->msrp;
    }

    /** First image URL or a placeholder. */
    public function getPrimaryImageAttribute(): string
    {
        $images = $this->images ?? [];
        return $images[0] ?? 'https://placehold.co/400x260/e9ecef/6c757d?text=No+Image';
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) {
            return $q;
        }
        return $q->where(function (Builder $q) use ($term) {
            $q->where('vin', 'like', "%{$term}%")
              ->orWhere('stock_number', 'like', "%{$term}%")
              ->orWhere('make', 'like', "%{$term}%")
              ->orWhere('model', 'like', "%{$term}%")
              ->orWhere('trim', 'like', "%{$term}%");
        });
    }

    public function scopeFilterCondition(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('condition', $val);
    }

    public function scopeFilterYear(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('year', (int) $val);
    }

    public function scopeFilterMake(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('make', $val);
    }

    public function scopeFilterModel(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('model', $val);
    }

    public function scopeFilterTrim(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('trim', $val);
    }

    public function scopeFilterBodyStyle(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('body_style', $val);
    }

    public function scopeFilterExteriorColor(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('exterior_color', $val);
    }

    public function scopeFilterTransmission(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('transmission', $val);
    }

    public function scopeFilterFuelType(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('fuel_type', $val);
    }

    public function scopeFilterDrivetrain(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('drivetrain', $val);
    }

    public function scopeFilterPriceMin(Builder $q, ?string $val): Builder
    {
        if (blank($val)) {
            return $q;
        }
        $price = (float) str_replace(',', '', $val);
        return $q->where(function (Builder $q) use ($price) {
            $q->where('sale_price', '>=', $price)
              ->orWhere(function (Builder $q) use ($price) {
                  $q->whereNull('sale_price')->where('msrp', '>=', $price);
              });
        });
    }

    public function scopeFilterPriceMax(Builder $q, ?string $val): Builder
    {
        if (blank($val)) {
            return $q;
        }
        $price = (float) str_replace(',', '', $val);
        return $q->where(function (Builder $q) use ($price) {
            $q->where('sale_price', '<=', $price)
              ->orWhere(function (Builder $q) use ($price) {
                  $q->whereNull('sale_price')->where('msrp', '<=', $price);
              });
        });
    }

    public function scopeFilterMileageMax(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('mileage', '<=', (int) str_replace(',', '', $val));
    }

    public function scopeFilterEngine(Builder $q, ?string $val): Builder
    {
        return blank($val) ? $q : $q->where('engine', $val);
    }
}
