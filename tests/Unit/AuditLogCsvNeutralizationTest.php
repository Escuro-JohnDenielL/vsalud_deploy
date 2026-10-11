<?php

use App\Support\Csv;

it('neutralizes spreadsheet formula triggers in text cells', function () {
    expect(Csv::neutralize('=1+1'))->toBe("'=1+1")
        ->and(Csv::neutralize('+SUM(A1:A2)'))->toBe("'+SUM(A1:A2)")
        ->and(Csv::neutralize('-2+3'))->toBe("'-2+3")
        ->and(Csv::neutralize('@SUM(1)'))->toBe("'@SUM(1)")
        ->and(Csv::neutralize("\t=cmd"))->toBe("'\t=cmd")
        ->and(Csv::neutralize("\r=cmd"))->toBe("'\r=cmd");
});

it('leaves ordinary text and empty cells untouched', function () {
    expect(Csv::neutralize("Maria O'Brien"))->toBe("Maria O'Brien")
        ->and(Csv::neutralize(''))->toBe('')
        ->and(Csv::neutralize(null))->toBe('');
});

it('prefixes a phone number that begins with plus', function () {
    expect(Csv::neutralize('+639171234567'))->toBe("'+639171234567");
});
