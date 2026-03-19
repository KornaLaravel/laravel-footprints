<?php

namespace Kyranb\Footprints\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Kyranb\Footprints\Tests\TestCase;
use Kyranb\Footprints\TrackingFilter;

class TrackingFilterTest extends TestCase
{
    public function test_tracks_get_requests()
    {
        $filter = new TrackingFilter;
        $request = Request::create('/test', 'GET');

        $this->assertTrue($filter->shouldTrack($request));
    }

    public function test_disabled_for_post_requests()
    {
        $filter = new TrackingFilter;
        $request = Request::create('/test', 'POST');

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_disabled_for_put_requests()
    {
        $filter = new TrackingFilter;
        $request = Request::create('/test', 'PUT');

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_disabled_on_authentication()
    {
        Config::set('footprints.disable_on_authentication', true);

        $user = new \Illuminate\Foundation\Auth\User;
        $user->id = 1;
        $this->actingAs($user);

        $filter = new TrackingFilter;
        $request = Request::create('/test', 'GET');

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_not_disabled_when_authentication_check_is_off()
    {
        Config::set('footprints.disable_on_authentication', false);

        $user = new \Illuminate\Foundation\Auth\User;
        $user->id = 1;
        $this->actingAs($user);

        $filter = new TrackingFilter;
        $request = Request::create('/test', 'GET');

        $this->assertTrue($filter->shouldTrack($request));
    }

    public function test_disabled_for_internal_links()
    {
        Config::set('footprints.disable_internal_links', true);

        $filter = new TrackingFilter;
        $request = Request::create('/test', 'GET', [], [], [], [
            'SERVER_NAME' => 'example.com',
            'HTTP_REFERER' => 'https://example.com/other-page',
        ]);

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_not_disabled_for_external_links()
    {
        Config::set('footprints.disable_internal_links', true);

        $filter = new TrackingFilter;
        $request = Request::create('/test', 'GET', [], [], [], [
            'SERVER_NAME' => 'example.com',
            'HTTP_REFERER' => 'https://google.com/search',
        ]);

        $this->assertTrue($filter->shouldTrack($request));
    }

    public function test_disabled_for_landing_page()
    {
        Config::set('footprints.landing_page_blacklist', ['blocked/page']);

        $filter = new TrackingFilter;
        $request = Request::create('/blocked/page', 'GET');

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_not_disabled_for_non_blacklisted_page()
    {
        Config::set('footprints.landing_page_blacklist', ['blocked/page']);

        $filter = new TrackingFilter;
        $request = Request::create('/allowed/page', 'GET');

        $this->assertTrue($filter->shouldTrack($request));
    }

    public function test_disabled_for_robots_tracking()
    {
        if (! class_exists(\Jaybizzle\CrawlerDetect\CrawlerDetect::class)) {
            $this->markTestSkipped('jaybizzle/crawler-detect is not installed');
        }

        Config::set('footprints.disable_robots_tracking', true);

        $filter = new TrackingFilter;
        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
        ]);

        $this->assertFalse($filter->shouldTrack($request));
    }
}
