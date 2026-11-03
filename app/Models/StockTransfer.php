<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'source_warehouse_id',
        'target_warehouse_id',
        'transfer_date',
        'transfer_reason',
        'approved_by_staff',
        'transfer_status',
    ];

    protected $casts = ['transfer_date' => 'date'];

    public function source()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function target()
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function details()
    {
        return $this->hasMany(TransferDetail::class);
    }

    // Reusable query scopes (used by StockTransferService::paginate)
    public function scopeSearchData(Builder $query, ?string $search): Builder
    {
        return $query->when(filled($search), fn (Builder $q) =>
            $q->where(function (Builder $w) use ($search) {
                $w->where('transfer_reason', 'like', "%{$search}%")
                  ->orWhere('approved_by_staff', 'like', "%{$search}%");
            })
        );
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('transfer_date', 'desc')->orderBy('id', 'desc');
    }
}
