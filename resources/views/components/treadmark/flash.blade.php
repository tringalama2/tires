@props(['on'])

<div
    x-data="{ shown: false, timeout: null }"
    x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2500); })"
    x-show="shown"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-1"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-1000"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;"
    {{ $attributes }}
>
    <x-treadmark.alert tone="success">{{ $slot->isEmpty() ? 'Saved.' : $slot }}</x-treadmark.alert>
</div>

{{--
  Tread Mark · Flash — Livewire event-triggered success message, auto-fades.
  <x-treadmark.flash on="profile-updated">{{ __('Saved.') }}</x-treadmark.flash>
--}}
