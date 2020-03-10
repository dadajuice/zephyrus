<?php namespace Zephyrus\Utilities;

class Gravatar
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $hash;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->hash = md5($email);
    }

    /**
     * Verifies if the given email has an existing Gravatar image.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        $uri = 'https://www.gravatar.com/avatar/' . $this->hash . '?d=404';
        $headers = @get_headers($uri);
        return preg_match("|200|", $headers[0]);
    }

    /**
     * Retrieves the compliant gravatar URL to obtain the image. The resulting
     * URL can be used in an image tag, background style, or else. Should call
     * the isAvailable method first if you want to manage non-existing Gravatar
     * outside the Gravatar system (which by default display a default image).
     *
     * @param int $size Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $default Default imageset to use [ 404 | mp | identicon | monsterid | wavatar ]
     * @return string
     * @source https://gravatar.com/site/implement/images/php/
     */
    public function getUrl(int $size = 0, string $default = "md"): string
    {
        return 'https://www.gravatar.com/avatar/' . $this->hash . '?s=' . $size . '&d=' . $default;
    }
}
