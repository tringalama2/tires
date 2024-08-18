@props(['for'])

@if ($errors->get($for))
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 flex flex-col space-y-1']) }}>
        @foreach ((array) $errors->get($for) as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
