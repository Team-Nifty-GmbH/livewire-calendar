<?php

namespace TeamNiftyGmbH\LivewireCalendar;

use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Component;
use TeamNiftyGmbH\LivewireCalendar\Models\Calendar as CalendarModel;
use TeamNiftyGmbH\LivewireCalendar\Models\CalendarEvent;

class LivewireCalendar extends Component
{
    public array $calendars;

    public array $activeCalendars;

    public array $selectableCalendars;

    public array $events;

    public array $event = [
        'calendar_id' => null,
        'starts_at' => null,
        'ends_at' => null,
        'is_all_day' => false,
    ];

    public bool $eventModal = false;

    public ?Carbon $gridStartsAt = null;

    public ?Carbon $gridEndsAt = null;

    // Settings
    public bool $editable = true;

    public bool $eventResizableFromStart = true;

    public Carbon $initialDate;

    // Can be set to:
    // dayGridMonth, dayGridWeek, dayGridDay,
    // timeGridWeek, timeGridDay, timeGrid,
    // listYear, listMonth, listWeek, listDay, list
    public string $initialView = 'dayGridMonth';

    public string $editEventComponent = 'calendar.new-event';

    protected array $rules = [
        'event.title' => 'required|string',
        'event.subtitle' => 'string',
        'event.description' => 'string',
        'event.starts_at' => 'required',
        'event.ends_at' => 'required',
        'event.is_all_day' => 'boolean',
        'event.calendar_id' => 'required|exists:calendars,id',
    ];

    /**
     * @return array
     */
    public function getListeners(): array
    {
        $channel = (new CalendarModel())->broadcastChannel(true);
        $listeners = [];
        $listeners['echo-private:' . $channel . ',.CalendarCreated'] = 'refreshCalendars';
        foreach ($this->activeCalendars as $calendar) {
            $calendarChannel = 'echo-private:' . $channel . '.' . $calendar;
            $listeners[$calendarChannel . ',.CalendarDeleted'] = 'refreshCalendars';
            $listeners[$calendarChannel . ',.CalendarUpdated'] = 'refreshCalendars';
            $listeners[$calendarChannel . ',.CalendarEventUpdated'] = 'refreshCalendarEvents';
            $listeners[$calendarChannel . ',.CalendarEventDeleted'] = 'refreshCalendarEvents';
            $listeners[$calendarChannel . ',.CalendarEventCreated'] = 'refreshCalendarEvents';
        }

        return $listeners;
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        $this->getCalendars();
        $this->activeCalendars = $this->getCalendarIds($this->calendars);
        $this->selectableCalendars = CalendarModel::query()
            ->whereIntegerInRaw('id', $this->activeCalendars)
            ->get(['id', 'name', 'color'])
            ->toArray();
        $this->events();
    }

    /**
     * @return void
     */
    public function refreshCalendarEvents(): void
    {
        $this->events();
        $this->emit('refreshCalendar');
    }

    /**
     * @return void
     */
    public function refreshCalendars(): void
    {
        $this->getCalendars();
        $this->emit('refreshCalendar');
    }

    /**
     * @return void
     */
    public function getCalendars(): void
    {
        $this->calendars = CalendarModel::query()
            ->whereNull('parent_id')
            ->with('children.parent')
            ->get()
            ->toArray();
    }

    /**
     * @param array $calendars
     * @return array
     */
    private function getCalendarIds(array $calendars): array
    {
        $activeCalendars = [];
        foreach ($calendars as $calendar) {
            $activeCalendars[] = $calendar['id'];
            $activeCalendars = array_merge($activeCalendars, $this->getCalendarIds($calendar['children']));
        }

        return $activeCalendars;
    }

    /**
     * @return void
     */
    public function updatedActiveCalendars(): void
    {
        $this->events();
        $this->emit('refreshCalendar');
    }

    /**
     * @param string $from
     * @param string $to
     * @return void
     */
    public function getEvents(string $from, string $to): void
    {
        $this->gridStartsAt = Carbon::parse($from)->subMonth();
        $this->gridEndsAt = Carbon::parse($to)->addMonth();
        $this->events();
        $this->skipRender();
    }

    /**
     * @return void
     */
    public function events(): void
    {
        $this->events = CalendarEvent::query()
            ->whereIntegerInRaw('calendar_id', $this->activeCalendars)
            ->where(function (Builder $query) {
                return $query
                    ->whereBetween('starts_at', [$this->gridStartsAt, $this->gridEndsAt])
                    ->orWhereBetween('ends_at', [$this->gridStartsAt, $this->gridEndsAt]);
            })
            ->get()
            ->map(function ($event) {
                return [
                    'allDay' => $event->is_all_day,
                    'start' => $event->starts_at,
                    'end' => $event->ends_at,
                    'title' => $event->title,
                    'color' => tailwind_to_hex($event->color),
                    'id' => $event->id,
                ];
            })
            ->toArray();
    }

    /**
     * @param string|null $dateString
     * @return void
     */
    public function onDayClick(?string $dateString = null): void
    {
        // This method gets called when a day is clicked
        $now = Carbon::now();
        $date = $dateString ?
            Carbon::parse($dateString)->setTime($now->hour, $now->minute)->floorMinutes(15) :
            $now;

        $this->reset('event');
        $this->event['starts_at'] = $date;
        $this->event['ends_at'] = $date;

        $this->eventModal = true;
        $this->skipRender();
    }

    /**
     * @param CalendarEvent $event
     * @return void
     */
    public function onEventClick(CalendarEvent $event): void
    {
        // This method is called when an event is clicked
        $this->event = $event->toArray();
        $this->eventModal = true;
    }

    /**
     * @param CalendarEvent $event
     * @param $data
     * @return void
     */
    public function onEventDropped(CalendarEvent $event, $data): void
    {
        // This method is called when an event was dropped on a new day
        $event->starts_at = Carbon::parse($data['start']);
        $event->ends_at = Carbon::parse($data['end'] ?? $data['start']);
        $event->save();
    }

    /**
     * @return void
     */
    public function save(): void
    {
        $event = CalendarEvent::query()
            ->whereKey($this->event['id'] ?? null)
            ->firstOrNew();

        $event->fill($this->event);

        $event->starts_at = Carbon::parse($event['starts_at']);
        $event->ends_at = Carbon::parse($event['ends_at']);
        $event->save();

        $this->refreshCalendarEvents();
        $this->eventModal = false;
    }

    /**
     * @return void
     */
    public function delete(): void
    {
        $event = CalendarEvent::query()
            ->whereKey($this->event['id'] ?? null)
            ->firstOrFail();

        $event->delete();

        $this->eventModal = false;
        $this->refreshCalendarEvents();
    }

    /**
     * @return Factory|View
     *
     * @throws Exception
     */
    public function render(): Factory|View
    {
        return view('components.calendar.calendar');
    }
}
