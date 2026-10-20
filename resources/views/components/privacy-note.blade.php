{{--
    Informational privacy line. It does not gate the form and does not set
    the consent flag. The link opens the existing full notice on the patron
    home page, which is informational only.
--}}
<p {{ $attributes->merge(['class' => 'vs-privacy-note']) }}>
    We collect your personal data to process your request.
    <a href="{{ route('patron.home') }}#privacy" target="_blank" rel="noopener noreferrer">Read the full Data Privacy Notice</a>.
</p>
