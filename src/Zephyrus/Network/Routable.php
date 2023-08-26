<?php namespace Zephyrus\Network;

interface Routable
{
    /**
     * Defines all the routes supported by this controller associated with
     * inner methods.
     */
    public function initializeRoutes(): void;
}
