<?php namespace Zephyrus\Application;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

abstract class Controller
{
    protected function render($page, $args = [])
    {
        $view = new View($page);
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
}