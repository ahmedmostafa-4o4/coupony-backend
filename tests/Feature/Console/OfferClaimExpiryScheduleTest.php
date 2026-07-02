<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class OfferClaimExpiryScheduleTest extends TestCase
{
    public function test_offer_claim_expiry_runs_every_minute_without_overlapping(): void
    {
        $event = collect(Schedule::events())->first(
            fn (Event|CallbackEvent $event): bool => str_contains($event->command ?? '', 'offer-claims:expire')
        );

        $this->assertNotNull($event, 'The offer claim expiry command is not scheduled.');
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
