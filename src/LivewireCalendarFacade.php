<?php

namespace TeamNiftyGmbH\LivewireCalendar;

use Illuminate\Support\Facades\Facade;

class LivewireCalendarFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'livewire-calendar';
    }
}
