<?php namespace Zephyrus\Application;

use Zephyrus\Network\Router;

interface Routable
{
    /**
     * Defines all the routes supported by this controller associated with
     * inner methods.
     *
     * @param Router $router
     */
    public static function initializeRoutes(Router $router);
}