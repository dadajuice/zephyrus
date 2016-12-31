<?php

/**
 * Simple example showcasing the basic usage of direct route definition instead
 * of using routable controllers. The routes directory can be removed if you
 * are only using controllers.
 */
$router->get("/example/static", function() {
    echo "it works !";
});