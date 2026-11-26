@extends('layouts.patron')

@section('title', 'Feedback')

@push('styles')
@vite('resources/css/patron/feedback.css')
@endpush

@section('content')
<div class="container">
  <div class="left-section">
    <section class="verified-review" id="reviewForm" aria-labelledby="verifiedReviewTitle">
      <h1 id="verifiedReviewTitle">Leave a verified review</h1>
      <p>Reviews are limited to completed bookings. Enter the tracking code and email address used for your reservation; your code and email will not appear with the published review.</p>

      <form method="POST" action="{{ route('patron.reviews.store') }}">
        @csrf
        <label for="review_tracking_code">Booking tracking code</label>
        <input type="text" id="review_tracking_code" name="review_tracking_code" maxlength="20"
               value="{{ old('review_tracking_code') }}" autocomplete="off" required>
        @error('review_tracking_code')
          <span class="error" role="alert">{{ $message }}</span>
        @enderror

        <label for="review_email">Booking email address</label>
        <input type="email" id="review_email" name="review_email" maxlength="254"
               value="{{ old('review_email') }}" autocomplete="email" required>
        @error('review_email')
          <span class="error" role="alert">{{ $message }}</span>
        @enderror

        <label for="review_rating">Your rating</label>
        <select id="review_rating" name="review_rating" required>
          <option value="">Choose a rating</option>
          @foreach ([5 => '5 - Excellent', 4 => '4 - Very good', 3 => '3 - Good', 2 => '2 - Fair', 1 => '1 - Poor'] as $rating => $label)
            <option value="{{ $rating }}" @selected((string) old('review_rating') === (string) $rating)>{{ $label }}</option>
          @endforeach
        </select>
        @error('review_rating')
          <span class="error" role="alert">{{ $message }}</span>
        @enderror

        <label for="review_message">Your review</label>
        <textarea id="review_message" name="review_message" rows="5" maxlength="2000"
                  placeholder="Share what you liked about your event..." required>{{ old('review_message') }}</textarea>
        @error('review_message')
          <span class="error" role="alert">{{ $message }}</span>
        @enderror

        <button type="submit">Submit verified review</button>
        <x-privacy-note text="Your tracking code and booking email are used only to verify the completed booking. They are not shown publicly." />
      </form>
    </section>

    <div class="contact-box">
      <h1>We'd Love to Hear From You</h1>
      <p>Please share your experience, suggestions, or any issues you faced. Your feedback helps us improve.</p>
      
      <form method="POST" action="{{ route('patron.feedback.store') }}">
        @csrf

        {{-- Spam controls. The honeypot sits off-screen, out of the tab order
             and hidden from assistive tech, so a person never fills it. The
             hidden token carries the encrypted form start time. --}}
        <div class="feedback-hp" aria-hidden="true">
          <label for="hp_ref">Reference</label>
          <input type="text" id="hp_ref" name="hp_ref" value="" tabindex="-1" autocomplete="off">
        </div>
        <input type="hidden" name="form_started_at" value="{{ vs_feedback_form_token() }}">

        {{-- Rendered with the 429 response when the limit is hit --}}
        @if (! empty($rateLimited))
        <div class="alert alert-error" role="alert">Too many messages were sent from this connection. Please wait a while, then try again.</div>
        @endif

        {{-- Full Name --}}
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="John Doe" value="{{ old('name') }}" required>
        @error('name')
        <span class="error">{{ $message }}</span>
        @enderror

        {{-- Email --}}
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" value="{{ old('email') }}" required>
        @error('email')
        <span class="error">{{ $message }}</span>
        @enderror

        {{-- Message --}}
        <label for="feedback">Your Feedback</label>
        <textarea id="feedback" name="message" rows="5" placeholder="Write your comments here..." required>{{ old('message') }}</textarea>
        @error('message')
        <span class="error">{{ $message }}</span>
        @enderror

        <button type="submit">Send Feedback</button>
        <x-privacy-note />
      </form>
    </div>

    <div class="contact-info">
      <h2>Visit Us</h2>
      @if (config('villasalud.contact.address'))
        <p><strong>Address:</strong> {{ config('villasalud.contact.address') }}</p>
      @endif
      @if (config('villasalud.contact.email'))
        <p><strong>Email:</strong> <a href="mailto:{{ config('villasalud.contact.email') }}">{{ config('villasalud.contact.email') }}</a></p>
      @endif
      @if (config('villasalud.social.facebook'))
        <p><strong>Facebook:</strong> <a href="{{ config('villasalud.social.facebook') }}" target="_blank" rel="noopener">Villa Salud Catering Services</a></p>
      @endif
      @if (config('villasalud.social.instagram'))
        <p><strong>Instagram:</strong> <a href="{{ config('villasalud.social.instagram') }}" target="_blank" rel="noopener">@villasaludcatering</a></p>
      @endif
      @if (config('villasalud.contact.phone'))
        <p><strong>Office Tel:</strong> {{ config('villasalud.contact.phone') }}</p>
      @endif
      @if (config('villasalud.contact.fax'))
        <p><strong>Tele-fax:</strong> {{ config('villasalud.contact.fax') }}</p>
      @endif
      @if (config('villasalud.contact.mobile'))
        <p><strong>Mobile:</strong> {{ config('villasalud.contact.mobile') }}</p>
      @endif
    </div>
  </div>

  <div class="right-section">
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.507360975934!2d121.06038407520304!3d14.49783818089743!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397cf6c40000001%3A0x2ea834ddefa91a11!2sVilla%20Salud%20Catering%20Services!5e0!3m2!1sen!2sph!4v1717830000000!5m2!1sen!2sph"
      allowfullscreen=""
      loading="lazy"
      title="Villa Salud Catering Services Location">
    </iframe>
  </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/patron/feedback.js')
@endpush