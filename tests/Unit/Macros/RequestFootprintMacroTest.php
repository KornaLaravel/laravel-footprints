<?php

namespace Kyranb\Footprints\Tests\Unit\Macros;

use Kyranb\Footprints\Tests\TestCase;

class RequestFootprintMacroTest extends TestCase
{
    public function test_footprint_macro_exists()
    {
        $request = $this->makeRequest('GET', '/test');

        $this->assertTrue($request::hasMacro('footprint'));
    }

    public function test_footprint_macro_returns_string()
    {
        $request = $this->makeRequest('GET', '/test');

        $footprint = $request->footprint();

        $this->assertIsString($footprint);
        $this->assertNotEmpty($footprint);
    }

    public function test_footprint_macro_returns_cookie_value_when_present()
    {
        $cookieName = config('footprints.cookie_name');
        $request = $this->makeRequest('GET', '/test', [], [$cookieName => 'my-tracking-id']);

        $footprint = $request->footprint();

        $this->assertEquals('my-tracking-id', $footprint);
    }

    public function test_footprint_macro_is_deterministic_without_cookie()
    {
        $request = $this->makeRequest('GET', '/test');

        $first = $request->footprint();
        $second = $request->footprint();

        $this->assertEquals($first, $second);
    }
}
