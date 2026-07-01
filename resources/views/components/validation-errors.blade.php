@if ($errors->has('socialstream'))
@elseif ($errors->any())
    <div {{ $attributes->merge(['class' => 'cs-errors']) }}>
        <div class="cs-errors__title">{{ __('Whoops! Something went wrong.') }}</div>

        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
