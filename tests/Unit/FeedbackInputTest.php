<?php

use App\Support\FeedbackInput;

it('accepts names with apostrophes, hyphens, digits, commas and accents', function () {
    $names = [
        "Maria O'Brien",
        'Anne-Marie Dela Cruz',
        "Ma. Jose O'Brien-Dela Cruz, 2nd",
        'Jose Maria Nunez',
        'Zoe Muller',
        'Dela Cruz, Jr.',
        'Maria-Jose',
    ];

    foreach ($names as $name) {
        expect(preg_match(FeedbackInput::NAME_PATTERN, $name))->toBe(1, "Rejected: {$name}");
    }
});

it('rejects names that carry markup or unexpected symbols', function () {
    $names = [
        '<script>alert(1)</script>',
        'John <b>Doe</b>',
        'user@example.com',
        'a; rm -rf /',
        "John\x00Doe",
        'John+Doe',
    ];

    foreach ($names as $name) {
        expect(preg_match(FeedbackInput::NAME_PATTERN, $name))->toBe(0, "Accepted: {$name}");
    }
});

it('requires the fill time to sit between the minimum and the maximum', function () {
    $now = 1_700_000_000;

    expect(FeedbackInput::isFillAgeAcceptable(null, $now))->toBeFalse()
        ->and(FeedbackInput::isFillAgeAcceptable($now, $now))->toBeFalse()
        ->and(FeedbackInput::isFillAgeAcceptable($now - 4, $now))->toBeFalse()
        ->and(FeedbackInput::isFillAgeAcceptable($now - 5, $now))->toBeTrue()
        ->and(FeedbackInput::isFillAgeAcceptable($now - 3600, $now))->toBeTrue()
        ->and(FeedbackInput::isFillAgeAcceptable($now - 10800, $now))->toBeTrue()
        ->and(FeedbackInput::isFillAgeAcceptable($now - 10801, $now))->toBeFalse();
});

it('strips control characters but keeps newlines in the message', function () {
    expect(FeedbackInput::cleanLine("J\x07ohn\x00 Doe"))->toBe('John Doe')
        ->and(FeedbackInput::cleanLine("Jane\nDoe"))->toBe('JaneDoe')
        ->and(FeedbackInput::cleanMessage("First\x07\nSecond"))->toBe("First\nSecond")
        ->and(FeedbackInput::cleanMessage("A\r\nB"))->toBe("A\nB")
        ->and(FeedbackInput::cleanMessage("Line\x0Btab"))->toBe('Linetab');
});

it('flags only a non-empty honeypot as tripped', function () {
    expect(FeedbackInput::honeypotTripped(null))->toBeFalse()
        ->and(FeedbackInput::honeypotTripped(''))->toBeFalse()
        ->and(FeedbackInput::honeypotTripped('  '))->toBeFalse()
        ->and(FeedbackInput::honeypotTripped('http://spam.example'))->toBeTrue();
});
