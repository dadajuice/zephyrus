<?php namespace Zephyrus\Utilities;

use Zephyrus\Network\Request;

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
        $this->urlParameter = $urlParameter;
        $page = Request::getParameter($urlParameter);
        $this->maxEntities = $maxEntities;
        $this->currentPage = (is_null($page)) ? 1 : $page;
        $this->maxPage = ceil($recordCount / $maxEntities);
        $this->pageUrl = Request::getPath();
        $this->pageQuery = $this->getQueryString();
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
        $pageStart = 1;
        $pageEnd = $this->maxPage;
        ob_start();

        // Afficher seulement si le nombre de page est plus grand que 1
        if (is_numeric($this->currentPage) && $this->maxPage > 1) {
            // S'il y a plus de 9 pages (maximum affichable)
            if ($this->maxPage > 9) {
                // Calcul temporaire pour la finale de la pagination
                $tmp = $this->currentPage;
                while(($pageEnd - $tmp) < 4) $tmp--;

                // Attribuer la page de départ
                if ($tmp != $this->currentPage) {
                    $pageStart = 1 + ($tmp - 5);
                }

                if ($this->currentPage > 5 && $this->currentPage == $tmp) {
                    $pageStart = 1 + ($this->currentPage - 5);
                }

                // Assigner les valeurs du tableaux à afficher
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
            } else {
                // S'il y a moins de 10 pages
                // Assigner les valeurs du tableaux à afficher
                for ($i = 1; $i <= $this->maxPage; $i++) {
                    if ($i == $this->currentPage) {
                        $pager[$i - 1] = "<span>$i</span>";
                    } else {
                        $pager[$i - 1] = '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . $i . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '">' . $i . '</a>';
                    }
                }
            }

            // Afficher le nombre total de pages
            echo '<div class="pager-wrapper">';
            print("<b>Page " . $this->currentPage . " de " . $this->maxPage . "</b>");
            print("<div>");

            // Afficher les liens précédent et premier
            if ($this->currentPage != 1) {
                // Afficher le lien 'premier'
                if ($this->currentPage - 4 > 1) {
                    echo '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=1' . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '" style="font-weight: 400; font-size: 1.2rem; padding-top: 8px; line-height: 0">«</a>';
                }

                // Afficher le lien 'précédent'
                echo '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . ($this->currentPage - 1) . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '" class="icon" style="font-size: 1rem; padding-top: 2px;">&lt;</a>';
            }

            // Afficher les pages
            for ($i = 0; $i < count($pager); $i++) {
                print($pager[$i]);
            }

            // Afficher les liens suivant et dernier
            if ($this->currentPage != $this->maxPage) {
                // Afficher le lien 'suivant'
                echo '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . ($this->currentPage + 1) . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '" class="icon" style="font-size: 1rem; padding-top: 2px;">&gt;</a>';

                // Afficher le lien 'dernier'
                if ($this->currentPage + 4 < $this->maxPage) {
                    echo '<a href="' . $this->pageUrl . '?' . $this->urlParameter . '=' . $this->maxPage . ((!empty($this->pageQuery)) ? '&' . $this->pageQuery : '') . '" style="font-weight: 400; font-size: 1.2rem; padding-top: 8px; line-height: 0">»</a>';
                }
            }

            // Terminer l'affichage
            print("</div></div>");
        }
        return ob_get_clean();
    }

    private function getQueryString()
    {
        $query = Request::getQuery();
        return preg_replace("/(&?" . $this->urlParameter . "=[0-9]*&?)/", "", $query);
    }
}