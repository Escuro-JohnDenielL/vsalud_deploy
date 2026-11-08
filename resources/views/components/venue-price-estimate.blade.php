@props([
    'venues' => collect(),
    'selectedRateId' => null,
    'errors' => null,
])

@php
    $ratesByVenue = collect($venues)
        ->mapWithKeys(fn ($venue) => [
            $venue->name => $venue->rates
                ->map(fn ($rate) => [
                    'id' => $rate->id,
                    'label' => $rate->label,
                    'period' => $rate->period,
                    'price' => (string) $rate->price,
                ])
                ->values()
                ->all(),
        ])
        ->all();
@endphp

<section class="venue-price-estimate"
         data-venue-price-estimate
         data-rates='@json($ratesByVenue)'
         data-selected-rate="{{ $selectedRateId }}"
         aria-labelledby="venuePriceEstimateTitle">
    <h3 id="venuePriceEstimateTitle">Venue price estimate</h3>
    <div class="venue-price-estimate__option" hidden>
        <label for="venue_rate_id">Venue pricing option</label>
        <select id="venue_rate_id" name="venue_rate_id" disabled></select>
    </div>
    @if($errors && $errors->has('venue_rate_id'))
        <p class="venue-price-estimate__error" role="alert">{{ $errors->first('venue_rate_id') }}</p>
    @endif
    <p class="venue-price-estimate__summary" aria-live="polite">
        Choose a venue to see its estimate.
    </p>
    <p class="venue-price-estimate__note">
        This is an estimate of the venue rental only. Villa Salud will confirm the final amount.
    </p>
</section>
