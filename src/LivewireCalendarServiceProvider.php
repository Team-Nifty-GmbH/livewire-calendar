<?php

namespace TeamNiftyGmbH\LivewireCalendar;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireCalendarServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerViews();

        $this->registerPublishables();

        $this->registerComponents();

        $this->registerDirectives();
    }

    private function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'livewire-calendar');
    }

    private function registerPublishables()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views/' => resource_path('views/vendor/livewire-calendar'),
            ], 'livewire-calendar:views');

            if (! class_exists('Calendar') && ! class_exists('CalendarEvents')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_calendars_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_calendars_table.php'),
                    __DIR__ . '/../database/migrations/create_calendar_events_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_calendar_events_table.php'),
                ], 'migrations');
            }
        }
    }

    private function registerComponents()
    {
        Livewire::component('livewire-calendar', LivewireCalendar::class);
    }

    private function registerDirectives()
    {
    }
}
