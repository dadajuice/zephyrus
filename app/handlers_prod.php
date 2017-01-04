<?php

use Zephyrus\Application\ErrorHandler;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Network\Response;

$errorHandler = ErrorHandler::getInstance();

$errorHandler->exception(function(Exception $e) {
    Response::abortInternalError();
});

$errorHandler->exception(function(DatabaseException $e) {
    Response::abortInternalError();
});

$errorHandler->exception(function(RouteNotFoundException $e) {
    Response::abortNotFound();
});

$errorHandler->exception(function(UnauthorizedAccessException $e) {
    Response::abortForbidden();
});

$errorHandler->exception(function(InvalidCsrfException $e) {
    Response::abortInternalError();
});