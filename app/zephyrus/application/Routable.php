<?php namespace Zephyrus\Application;

use Zephyrus\Network\BasicRouter;

interface Routable
{
    /**
     * Defines all the routes supported by this controller associated with
     * inner methods.
     *
     * @param BasicRouter $router
     */
    public static function initializeRoutes(BasicRouter $router);
}