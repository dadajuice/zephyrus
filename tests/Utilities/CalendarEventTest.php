<?php namespace Zephyrus\Tests\utilities;

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
        self::assertTrue(strpos($content, 'SUMMARY:test') !== false);
        self::assertTrue(strpos($content, 'BEGIN:VEVENT') !== false);
        self::assertTrue(strpos($content, 'DESCRIPTION:more depth') !== false);
        self::assertTrue(strpos($content, 'LOCATION:55 Dummy Street\, Gotham') !== false);
        self::assertTrue(strpos($content, 'URL;VALUE=URI:https://test.com') !== false);
        self::assertTrue(isset($response->getHeaders()['Content-Disposition']));
        self::assertEquals('attachment; filename="holiday.ics"', $response->getHeaders()['Content-Disposition']);
    }
}
