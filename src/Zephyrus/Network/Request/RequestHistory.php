<?php namespace Zephyrus\Network\Request;

use stdClass;
use Zephyrus\Core\Session;
use Zephyrus\Network\HttpMethod;
use Zephyrus\Network\Request;

class RequestHistory
{
    private const SESSION_KEY = "__ZF_HISTORY";

    /**
     * Adds the given URL to the client session history of the visited urls.
     *
     * @param Request $request
     */
    public function add(Request $request): void
    {
        $history = $this->getHistory();
        $history[] = $this->buildRecord($request);
        Session::set(self::SESSION_KEY, $history);
    }

    /**
     * Returns only the last recorded visited GET route the client did within his active session. Should be considered
     * for returning to the previous visited URL.
     *
     * @return string
     */
    public function getReferer(): string
    {
        $history = $this->getHistory();
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if ($history[$i]->method == HttpMethod::GET) {
                return $history[$i]->route;
            }
        }
        return "/";
    }

    /**
     * Returns the entire URLs visited by the client during his active session.
     *
     * @return array
     */
    public function getHistory(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function buildRecord(Request $request): stdClass
    {
        return (object) [
            'route' => $request->getRoute(),
            'method' => $request->getMethod(),
            'requested_url' => $request->getRequestedUrl(),
            'access' => time()
        ];
    }
}
