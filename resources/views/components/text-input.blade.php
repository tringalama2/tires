@props(['disabled' => false, 'required' => false])

<input {{ $disabled ? 'disabled' : '' }} {{ $required ? 'required' : '' }} {!! $attributes->class(['border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm', 'bg-gray-200' => $disabled]) !!}>
