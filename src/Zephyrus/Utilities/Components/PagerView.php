<?php namespace Zephyrus\Utilities\Components;

class PagerView
{
    public const MAIN_CSS_CLASSNAME = 'pager';

    private PagerModel $model;
    private int $currentPage;
    private int $maxPage;

    public function __construct(PagerModel $model, int $recordCount)
    {
        $this->model = $model;
        $this->currentPage = $this->model->getCurrentPage();
        $this->maxPage = ceil($recordCount / $this->model->getLimit());
        $this->validate();
    }

    /**
     * Displays directly to the output buffer the resulting HTML of the pager structure using an echo directive.
     */
    public function display()
    {
        echo $this;
    }

    /**
     * Retrieves the resulting HTML as string of the whole pager structure.
     *
     * @return string
     */
    public function getHtml(): string
    {
        if (!is_numeric($this->model->getCurrentPage()) || $this->maxPage < 1) {
            return "";
        }
        ob_start();
        $this->displayPager();
        return ob_get_clean();
    }

    /**
     * Alias method to display() allowing the usage of toString (e.g. echo $pager).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getHtml();
    }

    /**
     * Display the complete pager HTML architecture. The structure is quite simple: a root div with the pager class
     * wraps a series of a tags for the page links. Can be easily stylized with CSS.
     */
    private function displayPager()
    {
        $pager = ($this->maxPage > 9) ? $this->createFullPages() : $this->createSimplePages();
        echo '<div class="' . self::MAIN_CSS_CLASSNAME . '">';
        $this->displayLeftSide();
        for ($i = 0; $i < count($pager); $i++) {
            echo $pager[$i];
        }
        $this->displayRightSide();
        echo "</div>";
    }

    /**
     * Generates anchors to be displayed when max page is over 9.
     *
     * @return array
     */
    private function createFullPages(): array
    {
        $pageStart = 1;
        $pageEnd = $this->maxPage;
        $tmp = $this->currentPage;
        $pager = [];
        while (($pageEnd - $tmp) < 4) {
            $tmp--; // @codeCoverageIgnore
        }

        if ($tmp != $this->currentPage) {
            $pageStart = 1 + ($tmp - 5); // @codeCoverageIgnore
        }

        if ($this->currentPage > 5 && $this->currentPage == $tmp) {
            $pageStart = 1 + ($this->currentPage - 5);
        }

        $page = 0;
        for ($i = $pageStart; $i < $tmp; $i++) {
            $pager[$page] = '<a href="' . $this->buildHref($i) . '">' . $i . '</a>';
            $page++;
        }

        for ($i = $tmp; $i <= $pageEnd && $page < 9; $i++) {
            $pager[$page] = $this->buildAnchor($i);
            $page++;
        }
        return $pager;
    }

    /**
     * Generates anchors to be displayed when max page is under 10.
     *
     * @return array
     */
    private function createSimplePages(): array
    {
        $pager = [];
        for ($i = 1; $i <= $this->maxPage; $i++) {
            $pager[$i - 1] = $this->buildAnchor($i);
        }
        return $pager;
    }

    /**
     * Displays go to previous and first page.
     */
    private function displayLeftSide()
    {
        if ($this->currentPage != 1) {
            if ($this->currentPage - 4 > 1) {
                echo '<a href="' . $this->buildHref(1) . '">«</a>';
            }
            echo '<a href="' . $this->buildHref($this->currentPage - 1) . '">&lt;</a>';
        }
    }

    /**
     * Displays go to next and go to last page.
     */
    private function displayRightSide()
    {
        if ($this->currentPage != $this->maxPage) {
            echo '<a href="' . $this->buildHref($this->currentPage + 1) . '">&gt;</a>';
            if ($this->currentPage + 4 < $this->maxPage) {
                echo '<a href="' . $this->buildHref($this->maxPage) . '">»</a>';
            }
        }
    }

    /**
     * Generates a single anchor for the given page number. If the current viewing page is equals to the given page
     * number, it will be rendered as a span.
     *
     * @param string $pageNumber
     * @return string
     */
    private function buildAnchor(string $pageNumber): string
    {
        return ($pageNumber == $this->currentPage)
            ? "<span>$pageNumber</span>"
            : '<a href="' . $this->buildHref($pageNumber) . '">' . $pageNumber . '</a>';
    }

    /**
     * Prepares the needed href for a page number anchor. Makes sure to integrates the page argument within the
     * requested url properly (keeps all previous arguments).
     *
     * @param string $pageNumber
     * @return string
     */
    private function buildHref(string $pageNumber): string
    {
        $page = PagerParser::URL_PARAMETER . '=' . $pageNumber;
        $query = (!empty($this->model->getPageQuery())) ? '&' . $this->model->getPageQuery() : '';
        return $this->model->getPageUrl() . '?' . $page . $query;
    }

    /**
     * Safeguard to make sure the current page doesn't go over the possible max page number. If it goes above, simply
     * consider as if its page one. We don't want to cause an exception or an error as this class should serve as mere
     * displaying.
     */
    private function validate()
    {
        if ($this->currentPage < 1 || $this->currentPage > $this->maxPage) {
            $this->currentPage = 1;
        }
    }
}
