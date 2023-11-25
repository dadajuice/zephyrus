<?php namespace Zephyrus\Tests\Utilities\Extras;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\CalendarEvent;

class CalendarEventTest extends TestCase
{
    public function testResponse()
    {
        $start = strtotime("2020-01-05 18:30");
        $end = strtotime("2020-01-05 19:30");
        $iCal = new CalendarEvent($start, $end);
        $iCal->setSummary("test");
        $iCal->setDescription("more depth");
        $iCal->setLocation("55 Dummy Street, Gotham");
        $iCal->setFilename("holiday.ics");
        $iCal->setUri("https://test.com");
        self::assertEquals($end, $iCal->getEndDate());
        self::assertEquals($start, $iCal->getStartDate());
        self::assertEquals("more depth", $iCal->getDescription());
        self::assertEquals("test", $iCal->getSummary());
        self::assertEquals("55 Dummy Street, Gotham", $iCal->getLocation());
        self::assertEquals(13, strlen($iCal->getUid()));
        self::assertEquals("https://test.com", $iCal->getUri());
        $response = $iCal->buildResponse();
        $content = $response->getContent();
        self::assertTrue(str_contains($content, 'SUMMARY:test'));
        self::assertTrue(str_contains($content, 'BEGIN:VEVENT'));
        self::assertTrue(str_contains($content, 'DESCRIPTION:more depth'));
        self::assertTrue(str_contains($content, 'LOCATION:55 Dummy Street\, Gotham'));
        self::assertTrue(str_contains($content, 'URL;VALUE=URI:https://test.com'));
        self::assertTrue(isset($response->getHeaders()['Content-Disposition']));
        self::assertEquals('attachment; filename="holiday.ics"', $response->getHeaders()['Content-Disposition']);
    }
}
