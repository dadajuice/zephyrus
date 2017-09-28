<?php namespace Zephyrus\Database;

use PDO;
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
     * Return the next row from the current resultset obtained from the last
     * executed query. Automatically strip slashes that would have been stored
     * in database as escaping.
     *
     * @return array
     */
    public function next($fetchStyle = \PDO::FETCH_BOTH)
    {
        $row = $this->statement->fetch($fetchStyle);
        if (is_array($row)) {
            $this->sanitizeOutput($row);
        }
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

    /**
     * @param array $row
     */
    private function sanitizeOutput(&$row)
    {
        foreach ($row as &$value) {
            $value = strip_tags($value, $this->allowedHtmlTags);
        }
    }
}
