<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'item_code',
        'item_description',
        'supplier_id'];

    // Safety net: item_code is ALWAYS uppercase + trimmed when written.
    protected function itemCode(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => strtoupper(trim($value)),
        );
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transferDetails()
    {
        return $this->hasMany(TransferDetail::class);
    }

    public function scopeSearchData(Builder $query, ?string $search): Builder
    {
        return $query->when(filled($search), fn (Builder $q) =>
            $q->where(function (Builder $w) use ($search) {
                $w->where('item_code', 'like', "%{$search}%")
                  ->orWhere('item_description', 'like', "%{$search}%");
            })
        );
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}
