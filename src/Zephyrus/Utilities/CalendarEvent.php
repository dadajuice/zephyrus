<?php namespace Zephyrus\Utilities;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;
use Zephyrus\Network\ResponseFactory;

class CalendarEvent
{
    private const DEFAULT_FILENAME = "event.ics";

    /**
     * Filename to be used when downloading file (default is event.ics).
     *
     * @var string
     */
    private $filename = self::DEFAULT_FILENAME;

    /**
     * Unique event identifier.
     *
     * @var string
     */
    private $uid;

    /**
     * Geographic location of event.
     *
     * @var string
     */
    private $location;

    /**
     * Event title.
     *
     * @var string
     */
    private $summary;

    /**
     * Event textual potentially long description.
     *
     * @var string
     */
    private $description;

    /**
     * Url to event (must begin with http:// or https://).
     *
     * @var string
     */
    private $uri;

    /**
     * Start date time of event as timestamp (seconds since Epoch).
     *
     * @var int
     */
    private $startDate;

    /**
     * End date time of event as timestamp (seconds since Epoch).
     *
     * @var int
     */
    private $endDate;

    public function __construct(int $startTimestamp, int $endTimestamp)
    {
        $this->uid = uniqid();
        $this->startDate = $startTimestamp;
        $this->endDate = $endTimestamp;
    }

    /**
     * Output the ISC content along with the right HTTP headers. iCal requires a date format of "yyyymmddThhiissZ". The
     * "T" and "Z" characters are not placeholders, just plain ol' characters. The "T" character acts as a delimeter
     * between the date (yyyymmdd) and the time (hhiiss), and the "Z" states that the date is in UTC time. Note that if
     * you don't want to use UTC time, you must prepend your date-time values with a TZID property. See RFC 5545 section
     * 3.3.5.
     *
     * @see https://www.ietf.org/rfc/rfc5545.txt
     */
    public function buildResponse(): Response
    {
        ob_start();
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTEND:<?= $this->formatCalendarDate($this->endDate) . PHP_EOL ?>
UID:<?= $this->uid . PHP_EOL ?>
DTSTAMP:<?= $this->formatCalendarDate(time()) . PHP_EOL ?>
<?php if (!is_null($this->location)): ?>
LOCATION:<?= $this->escapeString($this->location) . PHP_EOL ?>
<?php endif; ?>
<?php if (!is_null($this->description)): ?>
DESCRIPTION:<?= $this->escapeString($this->description) . PHP_EOL ?>
<?php endif; ?>
<?php if (!is_null($this->uri)): ?>
URL;VALUE=URI:<?= $this->escapeString($this->uri) . PHP_EOL ?>
<?php endif; ?>
<?php if (!is_null($this->summary)): ?>
SUMMARY:<?= $this->escapeString($this->summary) . PHP_EOL ?>
<?php endif; ?>
DTSTART:<?= $this->formatCalendarDate($this->startDate) . PHP_EOL ?>
END:VEVENT
END:VCALENDAR<?php
        $content = ob_get_clean();
        return ResponseFactory::getInstance()->downloadContent($content, $this->getFilename(), ContentType::CALENDAR);
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation(string $location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     */
    public function setSummary(string $summary)
    {
        $this->summary = $summary;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri(string $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return int
     */
    public function getStartDate(): int
    {
        return $this->startDate;
    }

    /**
     * @return int
     */
    public function getEndDate(): int
    {
        return $this->endDate;
    }

    /**
     * Converts a unix timestamp to an ics-friendly format. Note that we are using "H" instead of "g" because
     * iCalendar's Time format requires 24-hour time (see RFC 5545 section 3.3.12 for info).
     *
     * @param int $timestamp
     * @return string
     */
    private function formatCalendarDate(int $timestamp): string
    {
        return date('Ymd\THis\Z', $timestamp);
    }

    /**
     * Correctly escapes a string for ics.
     *
     * @param string $string
     * @return string
     */
    private function escapeString(string $string): string
    {
        return preg_replace('/([,;])/','\\\$1', $string);
    }
}
