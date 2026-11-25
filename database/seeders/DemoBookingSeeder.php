<?php

namespace Database\Seeders;

use App\Models\Inquiry;
use App\Models\Patron;
use App\Models\Reservation;
use App\Services\VenuePricingService;
use App\Support\BookingRules;
use App\Support\VenueSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sample patron bookings used as presentation data.
 *
 * Every entry writes the same three rows the application writes for a real
 * booking: patron -> inquiry -> reservation, with the venue price estimate
 * carried inside form_data the way the reservation form stores it.
 *
 * Re-running is safe. A booking is skipped when the same patron already has an
 * inquiry for that venue and date, so nothing is duplicated or overwritten.
 */
class DemoBookingSeeder extends Seeder
{
    /**
     * Add more records here; one array entry is one confirmed booking.
     *
     * period must be AM or PM and time must be one of config('booking.time_slots').
     * rate_label points at the venue rate used for the total; when it does not
     * exist the first rate for that period is used instead.
     * When venue, event_type or theme_motif is "Others", the free text goes in
     * other_venue / other_event_type / other_theme_motif, exactly like the form.
     * status is optional and defaults to "completed" for past dates, "active"
     * otherwise, so the history stays truthful without extra bookkeeping.
     * created_at is optional and backdates the inquiry/reservation to when the
     * booking was actually placed. Both admin lists sort by created_at, so this
     * is what keeps demo rows from crowding the top of the page.
     */
    private const BOOKINGS = [
        [
            'name' => 'Asniah Embalsado',
            'email' => 'asniah.embalsado@example.com',
            'contact_number' => '09994409361',
            'date' => '2026-10-21',
            'period' => 'AM',
            'time' => '11am-3pm',
            'venue' => 'Villa I',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Birthday Package',
            'theme_motif' => 'Floral',
            'message' => 'For 50th bday type po na floral design yung theme',
            'created_at' => '2026-09-28 10:12:00',
        ],
        [
            'name' => 'Rene Virlon',
            'email' => 'renev02021986@gmail.com',
            'contact_number' => '09267950298',
            'date' => '2026-10-21',
            'period' => 'PM',
            'time' => '5pm-9pm',
            'venue' => 'Villa II',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Debut Package',
            'theme_motif' => 'Floral',
            'message' => 'Request po sana ng anak q na more on purple sa theme',
            'created_at' => '2026-09-30 14:05:00',
        ],
        [
            'name' => 'Angelica Suyom',
            'email' => 'angelicasuyom0329@gmail.com',
            'contact_number' => '09300080812',
            'date' => '2026-10-23',
            'period' => 'PM',
            'time' => '6pm-10pm',
            'venue' => 'Villa I',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Debut Package',
            'theme_motif' => 'Modern',
            'message' => 'Dalawa yung celebrant, pinaka request sa design is parehas name nila ang mailagay "Erika & Elija" @18',
            'created_at' => '2026-10-02 09:40:00',
        ],
        [
            'name' => 'Hoda Nhoor Tampogao',
            'email' => 'hodaptampogao@gmail.com',
            'contact_number' => '09298556832',
            'date' => '2026-10-24',
            'period' => 'AM',
            'time' => '9am-1pm',
            'venue' => 'Villa II',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Others',
            'other_event_type' => 'Aqiqah',
            'theme_motif' => 'Others',
            'other_theme_motif' => 'Kids Design, more on teddybear',
            'message' => "Muslim Event, let's talk more about the food na ise-serve",
            'created_at' => '2026-10-05 16:20:00',
        ],
        [
            'name' => 'Reniza Villa',
            'email' => 'reniza230@gmail.com',
            'contact_number' => '09453295574',
            'date' => '2026-10-24',
            'period' => 'PM',
            'time' => '5pm-9pm',
            'venue' => 'Villa II',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Wedding Package',
            'theme_motif' => 'Elegant',
            'message' => 'Request lang po na maraming white flowers per table',
            'created_at' => '2026-10-06 11:15:00',
        ],
        // November 2026: one date with a single booking, one with two, one with
        // three, so the calendar shows Available -> Limited -> Nearly Full
        // against the 4-events-per-day cap. Every venue/period pair on a shared
        // date is distinct, so no slot is double booked.
        [
            'name' => 'Asniah Embalsado',
            'email' => 'asniah.embalsado@example.com',
            'contact_number' => '09994409361',
            'date' => '2026-11-21',
            'period' => 'AM',
            'time' => '10am-2pm',
            'venue' => 'Villa I',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Wedding Package',
            'theme_motif' => 'Elegant',
            'message' => 'Second event po ng family, garden wedding setup with aisle flowers',
            'created_at' => '2026-11-02 09:05:00',
        ],
        [
            'name' => 'Rene Virlon',
            'email' => 'renev02021986@gmail.com',
            'contact_number' => '09267950298',
            'date' => '2026-11-22',
            'period' => 'PM',
            'time' => '4pm-8pm',
            'venue' => 'Villa I',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Debut Package',
            'theme_motif' => 'Floral',
            'message' => '18th birthday po, request ng purple and white floral backdrop',
            'created_at' => '2026-11-03 10:40:00',
        ],
        [
            'name' => 'Angelica Suyom',
            'email' => 'angelicasuyom0329@gmail.com',
            'contact_number' => '09300080812',
            'date' => '2026-11-22',
            'period' => 'AM',
            'time' => '11am-3pm',
            'venue' => 'Villa II',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Birthday Package',
            'theme_motif' => 'Modern',
            'message' => 'Kiddie party po, add balloon arch sa entrance',
            'created_at' => '2026-11-03 14:20:00',
        ],
        [
            'name' => 'Hoda Nhoor Tampogao',
            'email' => 'hodaptampogao@gmail.com',
            'contact_number' => '09298556832',
            'date' => '2026-11-28',
            'period' => 'AM',
            'time' => '9am-1pm',
            'venue' => 'Villa I',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Wedding Package',
            'theme_motif' => 'Rustic',
            'message' => 'Nikah ceremony po, separate seating for men and women',
            'created_at' => '2026-11-04 08:55:00',
        ],
        [
            'name' => 'Reniza Villa',
            'email' => 'reniza230@gmail.com',
            'contact_number' => '09453295574',
            'date' => '2026-11-28',
            'period' => 'PM',
            'time' => '5pm-9pm',
            'venue' => 'Villa II',
            'rate_label' => 'Air-conditioned',
            'event_type' => 'Birthday Package',
            'theme_motif' => 'Floral',
            'message' => '60th birthday po, white flowers per table and a photo booth corner',
            'created_at' => '2026-11-05 11:30:00',
        ],
        [
            'name' => 'Rene Virlon',
            'email' => 'renev02021986@gmail.com',
            'contact_number' => '09267950298',
            'date' => '2026-11-28',
            'period' => 'AM',
            'time' => '9am-1pm',
            'venue' => 'Elizabeth Hall',
            'rate_label' => '4-hour venue rental',
            'event_type' => 'Baptismal Package',
            'theme_motif' => 'Beach',
            'message' => 'Christening lunch reception po for 60 guests',
            'created_at' => '2026-11-06 15:10:00',
        ],
    ];

    public function run(): void
    {
        foreach (self::BOOKINGS as $booking) {
            DB::transaction(fn () => $this->seedBooking($booking));
        }
    }

    /**
     * @param  array<string, mixed>  $booking
     */
    private function seedBooking(array $booking): void
    {
        $date = BookingRules::parse($booking['date'] ?? null);

        if ($date === null) {
            $this->command?->warn("Skipped {$booking['name']}: date could not be read.");

            return;
        }

        $period = VenueSlot::normalizePeriod($booking['period'] ?? null)
            ?? VenueSlot::periodFromTimeText($booking['time'] ?? null);

        $patron = Patron::firstOrCreate(
            ['email' => $booking['email']],
            [
                'name' => $booking['name'],
                'contact_number' => $booking['contact_number'],
            ]
        );

        $existing = Inquiry::query()
            ->where('patron_id', $patron->patron_id)
            ->whereDate('date', $date)
            ->where('venue', $booking['venue'])
            ->first();

        if ($existing) {
            $this->backdate($existing, $booking['created_at'] ?? null);
            $this->command?->info("Skipped {$booking['name']} ({$booking['venue']}, {$date}): already seeded.");

            return;
        }

        $estimate = $this->priceEstimate($booking['venue'], $period, $booking['rate_label'] ?? null);
        $message = (string) ($booking['message'] ?? '');

        $formData = [
            'name' => $booking['name'],
            'email' => $booking['email'],
            'contact_number' => $booking['contact_number'],
            'date' => $date,
            'period' => $period,
            'time' => $booking['time'],
            'venue' => $booking['venue'],
            'event_type' => $booking['event_type'],
            'theme_motif' => $booking['theme_motif'],
            'message' => $message,
        ];

        if ($estimate !== null) {
            $formData['venue_price_estimate'] = $estimate;
        }

        // A free-text "Others" choice is stored twice, the same way the
        // reservation form does it: an "other_*" key in form_data and the
        // legacy column on the inquiry row.
        foreach (['venue', 'event_type', 'theme_motif'] as $field) {
            $other = $booking['other_' . $field] ?? null;

            if ($other !== null && $other !== '') {
                $formData[$field . '_other'] = $other;
            }
        }

        $status = $booking['status'] ?? (BookingRules::isPastDate($date) ? 'completed' : 'active');

        $inquiry = Inquiry::create([
            'patron_id' => $patron->patron_id,
            'created_by_type' => 'patron',
            'venue' => $booking['venue'],
            'other_venue' => $booking['other_venue'] ?? null,
            'event_type' => $booking['event_type'],
            'other_event_type' => $booking['other_event_type'] ?? null,
            'theme_motif' => $booking['theme_motif'],
            'other_theme_motif' => $booking['other_theme_motif'] ?? null,
            'date' => $date,
            'time' => $booking['time'],
            'message' => $message,
            'status' => $this->inquiryStatus($status),
            'form_data' => $formData,
        ]);

        // A confirmed conversion copies the free-text venue across, so the
        // reservation never just says "Others" (mirrors InquiryController).
        $reservationVenue = VenueSlot::normalize($booking['venue']) === 'others'
            ? ($booking['other_venue'] ?? $booking['venue'])
            : $booking['venue'];

        Reservation::create([
            'inquiry_id' => $inquiry->inquiry_id,
            'patron_id' => $patron->patron_id,
            'date' => $date,
            'time' => $booking['time'],
            'venue' => $reservationVenue,
            'event_type' => $booking['event_type'],
            'theme_motif' => $booking['theme_motif'],
            'message' => $message,
            'status' => $status,
            'total_amount' => $estimate['amount'] ?? $booking['amount'] ?? null,
            'form_data' => $formData,
        ]);

        $this->backdate($inquiry, $booking['created_at'] ?? null);

        $this->command?->info(
            "Seeded {$booking['name']} — {$booking['venue']}, {$date} {$booking['time']} ({$inquiry->tracking_code})."
        );
    }

    /**
     * Move the inquiry and its reservation back to when the booking was placed,
     * because both admin lists sort by created_at. created_at is not fillable,
     * so the column is written directly and the rest of the row is untouched.
     */
    private function backdate(Inquiry $inquiry, ?string $createdAt): void
    {
        if ($createdAt === null || trim($createdAt) === '') {
            return;
        }

        $timestamp = Carbon::parse($createdAt);

        DB::table('inquiry')
            ->where('inquiry_id', $inquiry->inquiry_id)
            ->update(['created_at' => $timestamp]);

        DB::table('reservation')
            ->where('inquiry_id', $inquiry->inquiry_id)
            ->update(['created_at' => $timestamp]);

        $inquiry->created_at = $timestamp;
    }

    /**
     * The same estimate the reservation form would have stored, or null when
     * the venue has no configured pricing yet.
     *
     * @return array{rate_id: int, venue: string, label: string, period: ?string, amount: string}|null
     */
    private function priceEstimate(string $venue, ?string $period, ?string $rateLabel): ?array
    {
        $pricing = app(VenuePricingService::class);
        $options = $pricing->options($venue, $period);

        if ($options->isEmpty()) {
            return null;
        }

        $rate = $rateLabel !== null
            ? $options->first(
                fn ($option) => mb_strtolower(trim((string) $option->label)) === mb_strtolower(trim($rateLabel))
            )
            : null;

        $rate ??= $options->first();

        try {
            return $pricing->estimate($venue, $period, $rate->id);
        } catch (\Throwable $e) {
            $this->command?->warn("No price estimate for {$venue}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Confirming an inquiry creates the reservation and stores "Completed" on
     * the inquiry itself, so an active reservation always pairs with a
     * Completed inquiry. Only a cancellation moves it to "Cancelled".
     */
    private function inquiryStatus(string $reservationStatus): string
    {
        return $reservationStatus === 'cancelled' ? 'Cancelled' : 'Completed';
    }
}
