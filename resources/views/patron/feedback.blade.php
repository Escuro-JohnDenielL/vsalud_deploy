@extends('layouts.patron')

@section('title', 'Feedback')

@push('styles')
@vite('resources/css/patron/feedback.css')
@endpush

@section('content')
<div class="container">
  <div class="left-section">
    <div class="contact-box">
      <h1>We'd Love to Hear From You</h1>
      <p>Please share your experience, suggestions, or any issues you faced. Your feedback helps us improve.</p>
      
      <form method="POST" action="{{ route('patron.feedback.store') }}">
        @csrf

        {{-- Success message --}}
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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
      </form>
    </div>

    <div class="contact-info">
      <h2>Visit Us</h2>
      <p><strong>Address:</strong> #229 MLQ St., Purok 3, New Lower Bicutan, Taguig City, Metro Manila, Philippines</p>
      <p><strong>Email:</strong> <a href="mailto:villasaludcateringservices@gmail.com">villasaludcateringservices@gmail.com</a></p>
      <p><strong>Facebook:</strong> <a href="https://www.facebook.com/villasaludcatering" target="_blank">Villa Salud Catering Services</a></p>
      <p><strong>Instagram:</strong> <a href="https://www.instagram.com/villasaludcatering?igsh=aDQ0bGQ4eW9pczlv" target="_blank">@villasaludcatering</a></p>
      <p><strong>Office Tel:</strong> 8837-68-01 / 8776-68-67</p>
      <p><strong>Tele-fax:</strong> 8856-21-81</p>
      <p><strong>Mobile:</strong> 0928-492-5493 / 0906-223-6120</p>
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