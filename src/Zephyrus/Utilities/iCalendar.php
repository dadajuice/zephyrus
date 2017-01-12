<?php namespace Zephyrus\Utilities;

class iCalendar
{
    /**
     * @var string Filename to be used when downloading file (default is
     * event.ics).
     */
    private $filename;

    /**
     * @var string Unique event identifier
     */
    private $uid;

    /**
     * @var string Geographic location of event
     */
    private $location;

    /**
     * @var string Event title
     */
    private $summary;

    /**
     * @var string Event textual potentially long description
     */
    private $description;

    /**
     * @var string Url to event (must begin with http:// or https://)
     */
    private $uri;

    /**
     * @var int Start date time of event as timestamp (seconds since Epoch)
     */
    private $startDate;

    /**
     * @var int End date time of event as timestamp (seconds since Epoch)
     */
    private $endDate;

    /**
     * Calendar constructor. Sets default values of object.
     */
    public function __construct()
    {
        $this->filename = "event.ics";
        $this->uid = uniqid();
    }

    /**
     * Output the ISC content along with the right HTTP headers. iCal requires
     * a date format of "yyyymmddThhiissZ". The "T" and "Z" characters are not
     * placeholders, just plain ol' characters. The "T" character acts as a
     * delimeter between the date (yyyymmdd) and the time (hhiiss), and the "Z"
     * states that the date is in UTC time. Note that if you don't want to use
     * UTC time, you must prepend your date-time values with a TZID property. See
     * RFC 5545 section 3.3.5.
     *
     * @see https://www.ietf.org/rfc/rfc5545.txt
     */
    public function send()
    {
        $this->sendHeaders();
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTEND:<?= $this->formatCalendarDate($this->endDate) . PHP_EOL ?>
UID:<?= $this->uid . PHP_EOL ?>
DTSTAMP:<?= $this->formatCalendarDate(time()) . PHP_EOL ?>
LOCATION:<?= $this->escapeString($this->location) . PHP_EOL ?>
DESCRIPTION:<?= $this->escapeString($this->description) . PHP_EOL ?>
URL;VALUE=URI:<?= $this->escapeString($this->uri) . PHP_EOL ?>
SUMMARY:<?= $this->escapeString($this->summary) . PHP_EOL ?>
DTSTART:<?= $this->formatCalendarDate($this->startDate) . PHP_EOL ?>
END:VEVENT
END:VCALENDAR<?php
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return int
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param int $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return int
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param int $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * The Content-Disposition: attachment header tells the browser to
     * save/open the file. The filename parameter is automatically set
     * using the object property of the same name.
     */
    private function sendHeaders()
    {
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $this->filename);
    }

    /**
     * Converts a unix timestamp to an ics-friendly format. Note that we are
     * using "H" instead of "g" because iCalendar's Time format requires 24-hour
     * time (see RFC 5545 section 3.3.12 for info).
     *
     * @param int $timestamp
     * @return string
     */
    private function formatCalendarDate($timestamp)
    {
        return date('Ymd\THis\Z', $timestamp);
    }

    /**
     * Correctly escapes a string.
     *
     * @param string $string
     * @return string
     */
    private function escapeString($string)
    {
        return preg_replace('/([\,;])/', '\\\$1', $string);
    }
}