<?php namespace Zephyrus\Utilities;

use Mailgun\Mailgun;
use Zephyrus\Application\Configuration;

class Mailer
{
    private static $config = null;

    /**
     * @var Mailgun
     */
    private $mailgun;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $cc;

    /**
     * @var string
     */
    private $bcc;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $htmlBody;

    /**
     * @var string
     */
    private $textBody;

    /**
     * @var array
     */
    private $attachments = [];

    private $recipients = [];

    public function __construct()
    {
        if (is_null(self::$config)) {
            self::$config = Configuration::getConfiguration('mailer');
        }
        $this->mailgun = new Mailgun(self::$config['mailgun_key']);
        $this->domain = self::$config['mailgun_domain'];
        $this->from = self::$config['from'];

    }

    public static function isValidEmail($email)
    {
        $mailgun = new Mailgun(Configuration::getConfiguration('mailer', 'mailgun_public_key'));
        $result = $mailgun->get("address/validate", array('address' => $email));
        return $result->http_response_body->is_valid;
    }

    public function setTemplate($template, $data = [])
    {
        $this->htmlBody = self::makeHtmlBody($template, $data);
        $this->textBody = self::makeTextBody($template, $data);
    }

    public function addRecipient($fullname, $email)
    {
        $this->recipients[] = $fullname . '<' . $email . '>';
    }

    public function addAttachment($path)
    {
        $this->attachments[] = $path;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param string $cc
     */
    public function setCc($cc)
    {
        $this->cc = $cc;
    }

    /**
     * @return string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @param string $bcc
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->htmlBody;
    }

    /**
     * @param string $htmlBody
     */
    public function setHtmlBody($htmlBody)
    {
        $this->htmlBody = $htmlBody;
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        return $this->textBody;
    }

    /**
     * @param string $textBody
     */
    public function setTextBody($textBody)
    {
        $this->textBody = $textBody;
    }

    public function send()
    {
        $data = $this->initializeData();
        foreach ($this->recipients as $recipient) {
            $data['to'] = $recipient;
            if (!empty($this->attachments)) {
                $this->mailgun->sendMessage($this->domain, $data, array('inline' => $this->attachments));
            } else {
                $this->mailgun->sendMessage($this->domain, $data);
            }
        }
    }

    public static function makeTextBody($template, $data)
    {
        ob_start();
        foreach ($data as $name => $var) {
            $$name = $var;
        }
        include(ROOT_DIR . '/app/views/mail_templates/' . $template . '_text.php');
        return ob_get_clean();
    }

    public static function makeHtmlBody($template, $data)
    {
        ob_start();
        foreach ($data as $name => $var) {
            $$name = $var;
        }
        include(ROOT_DIR . '/app/views/mail_templates/' . $template . '.php');
        return ob_get_clean();
    }

    private function initializeData()
    {
        $results = [
            'from' => $this->from,
            'subject' => $this->subject,
            'text' => $this->textBody,
            'html' => $this->htmlBody
        ];

        if (!empty($this->cc)) {
            $results['cc'] = $this->cc;
        }

        if (!empty($this->bcc)) {
            $results['bcc'] = $this->bcc;
        }

        return $results;
    }
}