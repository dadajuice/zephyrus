<?php namespace Zephyrus\Application;

use Pug\Pug;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

abstract class Controller
{
    protected function render($view, $args = [])
    {
        $pug = $this->buildPug();
        $args = array_merge($args, Flash::readAll());
        $this->html($pug->render(ROOT_DIR . '/app/views/' . $view . $pug->getExtension(), $args));
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

    private function buildPug()
    {
        $options = [
            'cache' => Configuration::getConfiguration('pug', 'cache'),
            'basedir' => ROOT_DIR . '/app/views'
        ];
        if (Configuration::getApplicationConfiguration('env') == "prod") {
            $options['upToDateCheck'] = false;
        }
        return new Pug($options);
    }
}