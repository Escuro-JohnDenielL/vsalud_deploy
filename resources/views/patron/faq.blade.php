@extends('layouts.patron')

@section('title', 'FAQs')

@push('styles')
    @vite('resources/css/patron/faq.css')
@endpush

@section('content')
<div class="faq-container">
    <h2>Frequently Asked Questions</h2>

    <div class="faq">
        <button class="faq-question">What's the difference between the Villa I & Villa II? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">The only difference of both venue is the capacity. Villa I can accommodate 
            up to 200 pax while Villa II can accommodate up to 300 pax. 
        </div>
    </div>

    <div class="faq">
        <button class="faq-question">The theme for our villa is more on white, pastel colors and I want it simple 
            but elegant. What venue do you recommend? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">We highly recommend Villa II if your theme is Elegant white and crystal since this venue has
            built-in crystal chandelier, mirror carpet and if you book this Villa, we can upgrade your chair into ghost chair
            for free charge!
        </div>
    </div>

    <div class="faq">
        <button class="faq-question">Do you have private swimming pool for rent? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">Yes! Our Private pool is good for 50 pax. Send 
            us a email at villasaludcateringservices@gmail.com for quotation and packages</div>
    </div>

    <div class="faq">
        <button class="faq-question">Do you have an accredited Coordinator? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">Yes! Our accredited coordinator team is The Events by design (TED) 
            owned by Ms. Rhose and Sir. Cris you can send an email for packages and more details. </div>
    </div>

    <div class="faq">
        <button class="faq-question">Do you offer venue rental only and caterer is from different supplier? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">Yes! We also offer a venue rental except for the whole month of December </div>
    </div>

    <div class="faq">
        <button class="faq-question">How to book your catering services and events place? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">You can email us at villasaludcateringservices@gmail.com
            and our reservation team will assist you. But we highly suggest to drop by
             in our office so our AE can discuss the package and show the venue before you book.</div>
    </div>

    <div class="faq">
        <button class="faq-question">What is your office hours? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">Our office is open daily except holidays from 9:00 am to 6:00 pm. For ocular 
            visit please contact us first to check if the venue is available for ocular visit. </div>
    </div>

    <div class="faq">
        <button class="faq-question">Do you have inhouse LED wall supplier? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">Yes! Our LED Wall is Php 15,000.00 only and the size is 9 X 12. 
            This rate is exclusive only to Villa Salud Events Place. </div>
    </div>

    <div class="faq">
        <button class="faq-question">Is pet allowed in your venue?<span class="arrow">&#9660;</span></button>
        <div class="faq-answer">Yes! But make sure to inform your Account executive that you will 
            bring your furbabies so they can give you a waiver regarding the
            rules & regulations of bringing pets in our facility. </div>
    </div>

    <div class="faq">
        <button class="faq-question">Do you accept Wedding Ceremony or Christian Wedding Ceremony within
             Villa Salud Events Place?<span class="arrow">&#9660;</span></button>
        <div class="faq-answer">Yes! Our clients have two options.
            Option 1: The Ceremony will be held at Villa I while the reception is 
            at Villa II Option 2: Ceremony and Reception will be held in one venue either Villa I or Villa II </div>
    </div>

    <div class="faq">
        <button class="faq-question">Where is the location of your office and events place?<span class="arrow">&#9660;</span></button>
        <div class="faq-answer">We are located at 229 Manuel L. Quezon St. Purok 3 New Lower Bicutan Taguig City. 
            Waze/Google Map Pin Location is Villa Salud Catering </div>
    </div>

    <div class="faq">
        <button class="faq-question">Do you offer floral arrangement for wedding entourage?<span class="arrow">&#9660;</span></button>
        <div class="faq-answer"> Yes! Send us an email at villasaludcateringservices@gmail.com for quotation. </div>
    </div>

    <div class="faq">
        <button class="faq-question">What occasion/event do you cater? <span class="arrow">&#9660;</span></button>
        <div class="faq-answer">We can cater all occasions like wedding, debut, anniversary party, gender reveal party, 
            kiddie party, adult party, seminars/conference and corporate events. </div>
    </div>

    <div class="faq">
        <button class="faq-question">Do you have mobile bar services?<span class="arrow">&#9660;</span></button>
        <div class="faq-answer">Yes! Send us an email at villasaludcateringservices@gmail.com for quotation and packages
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/patron/faq.js')
@endpush