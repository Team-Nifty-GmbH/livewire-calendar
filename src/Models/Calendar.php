<?php

namespace TeamNiftyGmbH\LivewireCalendar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TeamNiftyGmbH\LivewireCalendar\Enums\Color;

class Calendar extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'color' => Color::class,
    ];

    /**
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($calendar) {
            $calendar->calendarEvents()->delete();
        });
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Calendar::class, foreignKey: 'parent_id', localKey: 'id')->with('children');
    }

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Calendar::class, 'parent_id');
    }

    /**
     * @return HasMany
     */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }
}
