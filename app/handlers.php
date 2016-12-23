<?php

use Zephyrus\Application\ErrorHandler;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Exceptions\PayPalException;

$errorHandler = ErrorHandler::getInstance();

$errorHandler->exception(function(DatabaseException $e) {
    die($e->getMessage());
    //Response::abortInternalError();
});

$errorHandler->exception(function(RouteNotFoundException $e) {
    die($e->getMessage());
    //Response::abortNotFound();
});