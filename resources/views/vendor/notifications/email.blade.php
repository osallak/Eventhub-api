<x-mail::message>
{{-- Greeting --}}
# @lang('common.hello')

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}
@endforeach

{{-- Edit footer signature  --}}
@slot('footer')
        OK
    @endslot

</x-mail::message>
{{-- Edit footer signature  --}}
@slot('footer')
        OK2
    @endslot
