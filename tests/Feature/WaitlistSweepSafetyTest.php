<?php

use App\Models\Availability;
use App\Services\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/*
| The lazy waitlist sweep is best-effort: a cache or mail failure must never
| break the calendar or the waitlist join.
*/

uses(RefreshDatabase::class);

it('catches a cache failure inside the sweep', function () {
    Log::spy();
    Cache::shouldReceive('add')->once()->andThrow(new RuntimeException('cache down'));

    expect(app(WaitlistService::class)->sweepExpiredNotifications())->toBe(0);

    Log::shouldHaveReceived('warning')->once();
});

it('keeps the calendar up when the sweep throws', function () {
    $this->partialMock(WaitlistService::class, function ($mock) {
        $mock->shouldReceive('sweepExpiredNotifications')
            ->andThrow(new RuntimeException('sweep down'));
    });

    $this->getJson('/calendar/availability')
        ->assertOk()
        ->assertJsonStructure(['daily_cap', 'labels', 'cue_words', 'dates']);
});

it('keeps waitlist joins working when the sweep throws', function () {
    $date = now()->addDays(20)->format('Y-m-d');

    Availability::create([
        'date' => $date,
        'status' => 'Closed',
        'is_override' => true,
    ]);

    $this->partialMock(WaitlistService::class, function ($mock) {
        $mock->shouldReceive('sweepExpiredNotifications')
            ->andThrow(new RuntimeException('sweep down'));
    });

    $this->postJson(route('waitlist.join'), [
        'patron_name' => 'Maria Santos',
        'patron_email' => 'maria@example.test',
        'date' => $date,
    ])->assertOk()->assertJson(['success' => true]);
});
