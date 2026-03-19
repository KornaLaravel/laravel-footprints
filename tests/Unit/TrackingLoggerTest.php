<?php

namespace Kyranb\Footprints\Tests\Unit;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Kyranb\Footprints\Jobs\TrackVisit;
use Kyranb\Footprints\Tests\TestCase;
use Kyranb\Footprints\TrackingLogger;
use Kyranb\Footprints\Visit;

class TrackingLoggerTest extends TestCase
{
    public function test_logging_job_handled_async()
    {
        Config::set('footprints.async', true);
        Bus::fake();

        $request = $this->makeRequest('GET', '/test');

        $logger = new TrackingLogger;
        $logger->track($request);

        Bus::assertDispatched(TrackVisit::class);
    }

    public function test_logging_job_queued()
    {
        Config::set('footprints.async', false);

        $request = $this->makeRequest('GET', '/test');

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'landing_page' => 'test',
        ]);
    }

    public function test_attribution_data_ip()
    {
        Config::set('footprints.async', false);
        Config::set('footprints.attribution_ip', true);

        $request = $this->makeRequest('GET', '/test', [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'ip' => '192.168.1.1',
        ]);
    }

    public function test_attribution_data_ip_disabled()
    {
        Config::set('footprints.async', false);
        Config::set('footprints.attribution_ip', false);

        $request = $this->makeRequest('GET', '/test', [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'ip' => null,
        ]);
    }

    public function test_attribution_data_landing_domain()
    {
        Config::set('footprints.async', false);

        $request = $this->makeRequest('GET', '/test', [], [], [], ['SERVER_NAME' => 'example.com']);

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'landing_domain' => 'example.com',
        ]);
    }

    public function test_attribution_data_landing_page()
    {
        Config::set('footprints.async', false);

        $request = $this->makeRequest('GET', '/some/page');

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'landing_page' => 'some/page',
        ]);
    }

    public function test_attribution_data_landing_params()
    {
        Config::set('footprints.async', false);

        $request = $this->makeRequest('GET', '/test?foo=bar&baz=qux');

        $logger = new TrackingLogger;
        $logger->track($request);

        $visit = Visit::latest()->first();
        $this->assertStringContainsString('foo=bar', $visit->landing_params);
        $this->assertStringContainsString('baz=qux', $visit->landing_params);
    }

    public function test_attribution_data_referrer()
    {
        Config::set('footprints.async', false);

        $request = $this->makeRequest('GET', '/test', [], [], [], [
            'HTTP_REFERER' => 'https://google.com/search?q=test',
        ]);

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'referrer_url' => 'https://google.com/search?q=test',
            'referrer_domain' => 'google.com',
        ]);
    }

    public function test_attribution_data_utm()
    {
        Config::set('footprints.async', false);

        $request = $this->makeRequest('GET', '/test?utm_source=google&utm_campaign=spring&utm_medium=cpc&utm_term=shoes&utm_content=ad1');

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'utm_source' => 'google',
            'utm_campaign' => 'spring',
            'utm_medium' => 'cpc',
            'utm_term' => 'shoes',
            'utm_content' => 'ad1',
        ]);
    }

    public function test_attribution_data_referral()
    {
        Config::set('footprints.async', false);

        $request = $this->makeRequest('GET', '/test?ref=friend123');

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'referral' => 'friend123',
        ]);
    }

    public function test_attribution_data_custom()
    {
        Config::set('footprints.async', false);
        Config::set('footprints.custom_parameters', ['affiliate_id']);

        // Re-run migration to include the custom column
        $this->artisan('migrate:fresh');
        include_once __DIR__.'/../../database/migrations/create_footprints_table.php.stub';
        (new \CreateFootprintsTable)->up();

        $request = $this->makeRequest('GET', '/test?affiliate_id=abc123');

        $logger = new TrackingLogger;
        $logger->track($request);

        $this->assertDatabaseHas('visits', [
            'affiliate_id' => 'abc123',
        ]);
    }
}
