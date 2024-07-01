@props(['disabled' => false, 'options' => null, ])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm']) !!}>
    @if($options === null)
        {{ $slot }}
    @else
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $key => $value)
            <option value="{{ $key }}" @selected($selected == $key)>{{ $value }}</option>
@endforeach
@endif
</select>

