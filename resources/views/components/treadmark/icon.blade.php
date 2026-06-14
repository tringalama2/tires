@props(['name'])
{{-- Dynamic icon: <x-treadmark.icon name="tire" class="w-5 h-5" /> --}}
<x-dynamic-component :component="'treadmark.icon.' . $name" {{ $attributes }} />
