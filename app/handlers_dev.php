<?php

use Zephyrus\Application\ErrorHandler;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\DatabaseException;

$errorHandler = ErrorHandler::getInstance();

$errorHandler->exception(function(Exception $e) {
    die($e->getMessage() . ' : ' . $e->getTraceAsString());
});

$errorHandler->exception(function(DatabaseException $e) {
    die($e->getMessage() . ' : ' . $e->getTraceAsString());
});

$errorHandler->exception(function(RouteNotFoundException $e) {
    die($e->getMessage() . ' : ' . $e->getTraceAsString());
});

$errorHandler->exception(function(UnauthorizedAccessException $e) {
    die($e->getMessage() . ' : ' . $e->getTraceAsString());
});

$errorHandler->exception(function(InvalidCsrfException $e) {
    die($e->getMessage() . ' : ' . $e->getTraceAsString());
});