@extends('layouts.patron')

@section('title', 'Villa Salud Catering - Guidelines')

@push('styles')
    @vite(['resources/css/patron/guidelines.css'])
@endpush

@section('content')
<main class="guidelines-content">
    <section>
        <h2>Contact Information</h2>
        <p>
            Facebook: <a href="https://www.facebook.com/villasaludcatering" target="_blank">Villa Salud Catering Services</a><br>
            Email: <a href="mailto:villasaludcateringservices@gmail.com">villasaludcateringservices@gmail.com</a><br>
            Instagram: <a href="https://www.instagram.com/villasaludcatering?igsh=aDQ0bGQ4eW9pczlv" target="_blank">@villasaludcatering (IG)</a><br>
            Office Tel: 8837-68-01 / 8776-68-67<br>
            Tele-fax: 8856-21-81<br>
            Office CP: 0928-492-5493 / 0906-223-6120<br>
            Tita Beth (Owner): 09285021915 / 8359-56-32<br>
            Ms. Kathy Adriatico (Manager): 09065240734 / 8359-59-53<br>
            Ms. Camille R. Rafael (Asst. Manager): 0917-559-0131 / 8359-99-56
        </p>
    </section>

    <section>
        <h2>Reservation & Payment Policy</h2>
        <p><strong>Reservation Fee:</strong> Php 25,000.00 (Venue & Catering) / Php 5,000.00 (Swimming Pool). Non-refundable, non-consumable, non-transferable.</p>
        <p><strong>Payment Terms:</strong> 50% due one month prior, full payment due two weeks before event. Cash or approved credit cards only. Bank deposits must be advised via fax/email.</p>
        <p><strong>Cancellation Charges:</strong></p>
        <ul>
            <li>25%: 8 months or more before the event</li>
            <li>50%: 4 to 7 months before the event</li>
            <li>75%: 2 to 3 months before the event</li>
            <li>100%: 60 days or less before the event</li>
        </ul>
    </section>

    <section>
        <h2>Catering Coverage & Policies</h2>
        <ul>
            <li>Excess guests will be charged accordingly.</li>
            <li>Up to 10% reduction in guest count allowed 2 weeks before the event.</li>
            <li>No refund for unconsumed catering items.</li>
            <li>Clients are liable for toll fees and external catering incidentals.</li>
            <li>Outside food/drinks are prohibited unless pre-approved in writing.</li>
            <li>Villa Salud is not liable for spoiled food due to client delay in serving.</li>
            <li>All food must be consumed immediately; Villa Salud is not liable for takeout food-related illness.</li>
        </ul>
    </section>

    <section>
        <h2>Function Set-up</h2>
        <ul>
            <li>Villa Salud controls layout and equipment setup.</li>
            <li>No attachment to walls, ceilings, or floors is allowed.</li>
            <li>Damages by client/guests will be charged based on market value.</li>
            <li>Client must secure permits for offsite catering setups.</li>
        </ul>
    </section>

    <section>
        <h2>Safety and Security</h2>
        <ul>
            <li>No flammable or explosive materials allowed.</li>
            <li>No fireworks allowed during events.</li>
            <li>Villa Salud is not responsible for lost or damaged personal items.</li>
            <li>Client assumes liability for any injury or damages caused.</li>
        </ul>
    </section>

    <section>
        <h2>Swimming Pool Rental Terms</h2>
        <p><strong>Rates:</strong> AM (7:00 AM – 5:00 PM): Php 8,500 | PM (7:00 PM – 5:00 AM): Php 9,500</p>
        <p><strong>Standard Amenities:</strong> 50 monobloc chairs, 6 tables, buffet table, griller</p>
        <p><strong>Extra Charges:</strong></p>
        <ul>
            <li>Videoke: Php 1,000</li>
            <li>Mobile System: Php 8,000 (4 hrs)</li>
            <li>Room: Php 2,500 (12 hrs)</li>
            <li>Photobooth: Php 5,000 (3 hrs)</li>
            <li>Mobile bar: Php 5,000 and up</li>
        </ul>
        <p><strong>Important Notes:</strong></p>
        <ul>
            <li>No lifeguard on duty – swim at your own risk.</li>
            <li>Children must be accompanied by adults.</li>
            <li>Clients must follow capacity limits and LGU guidelines.</li>
            <li>Overtime, excess guests, and damages incur additional charges.</li>
        </ul>
    </section>

    <section>
        <h2>Guidelines for Outside Caterers & Suppliers</h2>
        <ul>
            <li>No removal or rearranging of Villa Salud's equipment without permission.</li>
            <li>Cooking is not allowed. Food warming only.</li>
            <li>No dishwashing in premises. Clean-up required.</li>
            <li>Sufficient garbage bags must be provided by suppliers.</li>
            <li>Staff must wear proper uniforms; no casual clothing allowed.</li>
            <li>No bathing for staff in venue.</li>
            <li>Ingress: AM – 6:00 AM | PM – 6:00 PM</li>
            <li>Vehicles cannot park inside Villa Salud. Coordinate for parking.</li>
            <li>Vicinity lights/fans allowed 30 mins before the event only.</li>
            <li>All equipment must be cleared post-event with gate pass.</li>
        </ul>
    </section>

    <section>
        <h2>Force Majeure Clause</h2>
        <p>Villa Salud is not liable for performance failures caused by uncontrollable events (e.g., natural disasters, war, governmental regulations). In such cases, Villa Salud reserves the right to provide alternative accommodations or menus as fulfillment of the contract.</p>
    </section>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navbarMenu = document.querySelector('.navbar ul');
    
    if (hamburger && navbarMenu) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navbarMenu.classList.toggle('active');
        });
        
        // Close menu when clicking on a link (for mobile)
        const navLinks = document.querySelectorAll('.navbar a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navbarMenu.classList.remove('active');
            });
        });
    }
});
</script>
@endpush
@endsection