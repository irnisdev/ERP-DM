<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic attachment agar file dapat dipakai oleh Employee, Product, Customer,
 * Transaction, Asset, Branch, BPJS, Supplier, dll.
 */
class MediaAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_file_id',
        'attachable_type',
        'attachable_id',
        'collection_name',
    ];

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}