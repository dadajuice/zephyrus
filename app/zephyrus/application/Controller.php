<?php namespace Zephyrus\Application;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Utilities\Pager;

abstract class Controller
{
    protected function render($page, $args = [], Pager $pager = null)
    {
        $view = new View($page);
        if (!is_null($pager)) {
            $view->setPager($pager);
        }
        echo $view->render($args);
    }

    protected function html($data)
    {
        Response::sendResponseCode();
        Response::sendContentType(ContentType::HTML);
        echo $data;
        exit;
    }

    protected function json($data)
    {
        Response::sendResponseCode();
        Response::sendContentType(ContentType::JSON);
        echo json_encode($data);
        exit;
    }

    protected function xml($data, $root = "")
    {
        Response::sendResponseCode();
        Response::sendContentType(ContentType::XML);
        if (is_array($data)) {
            $xml = new \SimpleXMLElement('<' . $root . '/>');
            array_walk_recursive($data, array ($xml, 'addChild'));
            echo $xml->asXML();
            exit;
        }
        if ($data instanceof \SimpleXMLElement) {
            echo $data->asXML();
            exit;
        }
        throw new \RuntimeException("Cannot parse specified data as XML");
    }

    protected static function bind($method)
    {
        return [get_called_class(), $method];
    }
}