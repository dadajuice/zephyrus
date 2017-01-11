<?php namespace Zephyrus\Security;

class SessionDecoy
{
    /**
     * @var string[] array of decoy cookie names to be sent on session start
     */
    private $decoys = [];

    public function __construct(array $config)
    {
        if (isset($config['decoys'])) {
            if (is_numeric($config['decoys'])) {
                $this->addRandomDecoys($config['decoys']);
            } elseif (is_array($config['decoys'])) {
                foreach ($config['decoys'] as $decoyName) {
                    $this->addDecoy($decoyName);
                }
            }
        }
    }

    /**
     * Throw decoy cookies at session start which are configured exactly as the
     * session cookie. This doesn't contribute in pure security measures, but
     * contribute in hiding server footprints and add a little more confusion
     * to the overall communication.
     */
    public function throwDecoys()
    {
        $params = session_get_cookie_params();
        $len = strlen(session_id());

        foreach ($this->decoys as $decoy) {
            $value = Cryptography::randomString($len);
            setcookie(
                $decoy,
                $value,
                $params['lifetime'],
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    /**
     * Add a decoy cookie with the specified name that will be thrown along the
     * session cookie. This doesn't contribute in pure security measures, but
     * contribute in hiding server footprints and add a little more confusion
     * to the overall communication.
     *
     * @param string $name
     */
    public function addDecoy($name)
    {
        $this->decoys[] = $name;
    }

    /**
     * Add a certain amount of random decoy cookies that will be sent along
     * the session cookie. This doesn't contribute in pure security measures,
     * but contribute in hiding server footprints and add a little more
     * confusion to the overall communication.
     *
     * @param int $count
     */
    public function addRandomDecoys($count)
    {
        for ($i = 0; $i < $count; ++$i) {
            $this->addDecoy(Cryptography::randomString(20));
        }
    }

    /**
     * Carefully expires all decoy cookies. Should be called only if decoys are
     * set and on session destroy.
     *
     * @see destroy()
     */
    public function destroyDecoys()
    {
        foreach ($this->decoys as $decoy) {
            setcookie($decoy, '', 1);
            setcookie($decoy, false);
            unset($_COOKIE[$decoy]);
        }
    }
}