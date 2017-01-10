<?php namespace Zephyrus\Utilities;

use Zephyrus\Network\RequestFactory;

class Pager
{
    const PAGE_MAX_ENTITIES = 50;
    const URL_PARAMETER = 'page';

    /**
     * @var int
     */
    private $currentPage;

    /**
     * @var int
     */
    private $maxPage;

    /**
     * @var string
     */
    private $pageUrl;

    /**
     * @var string
     */
    private $pageQuery;

    /**
     * @var int
     */
    private $maxEntities;

    /**
     * @var
     */
    private $urlParameter;

    public function __construct($recordCount, $maxEntities = self::PAGE_MAX_ENTITIES, $urlParameter = self::URL_PARAMETER)
    {
        $request = RequestFactory::create();
        $this->urlParameter = $urlParameter;
        $page = $request->getParameter($urlParameter);
        $this->maxEntities = $maxEntities;
        $this->currentPage = (is_null($page)) ? 1 : $page;
        $this->maxPage = ceil($recordCount / $maxEntities);
        $this->pageUrl = $request->getPath();
        $this->pageQuery = $this->getQueryString($request->getQuery());
        $this->validate();
    }

    private function validate()
    {
        if ($this->currentPage < 1 || $this->currentPage > $this->maxPage) {
            redirect($this->pageUrl);
        }
    }

    public function getSqlLimit()
    {
        $offset = $this->maxEntities * ($this->currentPage - 1);
        return " LIMIT $offset, $this->maxEntities";
    }

    /**
     * @return int
     */
    public function getMaxEntitiesPerPage()
    {
        return $this->maxEntities;
    }

    public function display()
    {
        echo $this;
    }

    public function __toString()
    {
        if (!is_numeric($this->currentPage) || $this->maxPage < 1) {
            return "";
        }
        ob_start();
        $this->displayPager();
        return ob_get_clean();
    }

    /**
     * Generates anchors to be displayed when max page is over 9
     *
     * @return array
     */
    private function createFullPages()
    {
        $pageStart = 1;
        $pageEnd = $this->maxPage;
        $tmp = $this->currentPage;
        $pager = [];
        while(($pageEnd - $tmp) < 4) $tmp--;

        if ($tmp != $this->currentPage) {
            $pageStart = 1 + ($tmp - 5);
        }

        if ($this->currentPage > 5 && $this->currentPage == $tmp) {
            $pageStart = 1 + ($this->currentPage - 5);
        }

        $page = 0;
        for ($i = $pageStart; $i < $tmp; $i++) {
            $pager[$page] = '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . $i . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '">' . $i . '</a>';
            $page++;
        }

        for ($i = $tmp; $i <= $pageEnd && $page < 9; $i++) {
            if ($i == $this->currentPage) {
                $pager[$page] = "<span>$i</span>";
            } else {
                $pager[$page] = '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . $i . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '">' . $i . '</a>';
            }
            $page++;
        }

        return $pager;
    }

    /**
     * Generates anchors to be displayed when max page is under 10.
     *
     * @return array
     */
    private function createSimplePages()
    {
        $pager = [];
        for ($i = 1; $i <= $this->maxPage; $i++) {
            if ($i == $this->currentPage) {
                $pager[$i - 1] = "<span>$i</span>";
            } else {
                $pager[$i - 1] = '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . $i . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '">' . $i . '</a>';
            }
        }
        return $pager;
    }

    /**
     * Display the complete pager architecture.
     */
    private function displayPager()
    {
        $pager = ($this->maxPage > 9) ? $this->createFullPages() : $this->createSimplePages();
        echo '<div class="pager">';
        $this->displayLeftSide();
        for ($i = 0; $i < count($pager); $i++) {
            print($pager[$i]);
        }
        $this->displayRightSide();
        print("</div>");
    }

    /**
     * Displays go to previous and first page.
     */
    private function displayLeftSide()
    {
        if ($this->currentPage != 1) {
            if ($this->currentPage - 4 > 1) {
                echo '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=1' . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '" style="font-weight: 400; font-size: 1.2rem; padding-top: 8px; line-height: 0">«</a>';
            }
            echo '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . ($this->currentPage - 1) . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '" class="icon" style="font-size: 1rem; padding-top: 2px;">&lt;</a>';
        }
    }

    /**
     * Displays go to next and go to last page.
     */
    private function displayRightSide()
    {
        if ($this->currentPage != $this->maxPage) {
            echo '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . ($this->currentPage + 1) . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '" class="icon" style="font-size: 1rem; padding-top: 2px;">&gt;</a>';
            if ($this->currentPage + 4 < $this->maxPage) {
                echo '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . $this->maxPage . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '" style="font-weight: 400; font-size: 1.2rem; padding-top: 8px; line-height: 0">»</a>';
            }
        }
    }

    private function getQueryString($query)
    {
        return preg_replace("/(&?" . $this->urlParameter . "=[0-9]*&?)/", "", $query);
    }
}