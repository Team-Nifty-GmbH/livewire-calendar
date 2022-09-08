@props(['level' => 0, 'calendar'])
<div style="padding-left: {{$level * 10}}px">
    @if(count($calendar['children']))
        <div class="text-sm font-medium text-secondary-700 dark:text-gray-400 font-bold pt-2">
            {{$calendar['name']}}
        </div>
    @endif
    @forelse($calendar['children'] ?? [] as $calendarEntry)
        <x-calendar.calendar :calendar="$calendarEntry" :level="$level + 1"/>
    @empty
        <x-checkbox :id="(string)\Illuminate\Support\Str::uuid()" class="checked:{{$calendar['color']}}"
                    :value="$calendar['id']" :label="$calendar['name']" wire:model="activeCalendars"/>
    @endforelse
</div>
