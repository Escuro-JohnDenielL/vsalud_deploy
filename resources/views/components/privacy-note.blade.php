{{--
    Informational privacy line for the forms that collect personal data. It
    does not gate the form and does not set the consent flag; the reservation
    form keeps its own agreement gate. The link opens the standalone notice,
    which is informational only.
--}}
@props(['text' => 'We collect your personal data to process your request.'])

<p {{ $attributes->merge(['class' => 'vs-privacy-note']) }}>
    {{ $text }}
    <a href="{{ route('patron.privacy') }}" target="_blank" rel="noopener noreferrer">Read the full Data Privacy Notice</a>.
</p>
