<x-select :clearable="false" :label="__('Calendar') . '*'" wire:model="event.calendar_id"
          :options="$this->selectableCalendars" option-label="name" option-value="id"/>
<x-input x-ref="autofocus" :label="__('Title') . '*'" wire:model="event.title"/>
<x-textarea :label="__('Description')" wire:model="event.description"/>
<x-checkbox :label="__('all-day')" wire:model="event.is_all_day"/>
<x-datetime-picker
    wire:key="{{Str::uuid()->toString()}}"
    id="{{Str::uuid()->toString()}}"
    :clearable="false"
    :without-time="$this->event['is_all_day']"
    time-format="24"
    :label="__('starts:')"
    wire:model.defer="event.starts_at"
    display-format="DD.MM.YYYY{{$this->event['is_all_day'] ? '' : ' HH:mm'}}"
    without-timezone="true"

/>
<x-datetime-picker
    id="{{Str::uuid()->toString()}}"
    wire:key="{{Str::uuid()->toString()}}"
    :clearable="false"
    :without-time="$this->event['is_all_day']"
    time-format="24"
    :label="__('ends:')"
    wire:model.defer="event.ends_at"
    display-format="DD.MM.YYYY{{$this->event['is_all_day'] ? '' : ' HH:mm'}}"
    without-timezone="true"
/>
