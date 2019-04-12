<?php namespace Zephyrus\Database;

use PDOStatement;

class DatabaseStatement
{
    /**
     * @var PDOStatement
     */
    private $statement = null;

    /**
     * @var string
     */
    private $allowedHtmlTags = "";

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * Return the next row from the current result set obtained from the last
     * executed query. Automatically strip slashes that would have been stored
     * in database as escaping.
     *
     * @param int $fetchStyle
     * @return array
     */
    public function next($fetchStyle = \PDO::FETCH_BOTH)
    {
        $row = $this->statement->fetch($fetchStyle);
        $this->sanitizeOutput($row);
        return $row;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->statement->rowCount();
    }

    /**
     * @return string
     */
    public function getAllowedHtmlTags()
    {
        return $this->allowedHtmlTags;
    }

    /**
     * @param string $allowedHtmlTags
     */
    public function setAllowedHtmlTags($allowedHtmlTags)
    {
        $this->allowedHtmlTags = $allowedHtmlTags;
    }

    /**
     * @return bool
     */
    public function isDirectHtmlTagsAllowed()
    {
        return !empty($this->allowedHtmlTags);
    }

    /**
     * @param string $tag
     */
    public function addAllowedHtmlTag($tag)
    {
        $this->allowedHtmlTags .= $tag;
    }

    /**
     * Clear allowed tags.
     */
    public function disallowDirectHtmlTags()
    {
        $this->allowedHtmlTags = "";
    }

    private function sanitizeOutput(&$row)
    {
        if (is_array($row)) {
            $this->sanitizeArrayOutput($row);
        }
        if (is_object($row)) {
            $this->sanitizeObjectOutput($row);
        }
    }

    private function sanitizeObjectOutput(&$row)
    {
        $properties = get_object_vars($row);
        $this->sanitizeArrayOutput($properties);
        $row = (object) $properties;
    }

    private function sanitizeArrayOutput(&$row)
    {
        foreach ($row as &$value) {
            if (!is_null($value)) {
                $value = $this->sanitize($value);
            }
        }
    }

    private function sanitize($value)
    {
        return strip_tags($value, $this->allowedHtmlTags);
    }
}
