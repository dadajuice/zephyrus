<?php namespace Zephyrus\Utilities;

use InvalidArgumentException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Zephyrus\Utilities\FileSystem\File;

class Email
{
    private PHPMailer $phpMailer;
    private bool $useSmtp;

    /**
     * @param PHPMailer $phpMailer
     * @param bool $useSmtp
     * @param string $subject
     */
    public function __construct(PHPMailer $phpMailer, bool $useSmtp, string $subject)
    {
        $this->phpMailer = $phpMailer;
        $this->phpMailer->Subject = $subject;
        $this->useSmtp = $useSmtp;
    }

    public function send(bool $asHtml = true): string
    {
        $this->phpMailer->IsHTML($asHtml);
        if (!$this->phpMailer->preSend()) {
            echo $this->phpMailer->ErrorInfo;
            //TODO: throw exception
        }
        if ($this->useSmtp) {
            if (!$this->phpMailer->postSend()) {
                echo $this->phpMailer->ErrorInfo;
                //TODO: throw exception
            }
        }
        return $this->phpMailer->getSentMIMEMessage();
    }

    /**
     * Applies the given $content as the email body that will be sent to recipients. The $altContent is used for clients
     * which cannot render HTML content.
     *
     * @param string $content
     * @param string $altContent
     */
    public function setBody(string $content, string $altContent = ""): void
    {
        $this->phpMailer->Body = $content;
        $this->phpMailer->AltBody = $altContent;
    }

    public function setFrom(string $email, string $name): void
    {
        try {
            $this->phpMailer->setFrom($email, $name);
        } catch (Exception $e) {
            // TODO: Invalid Address ...
        }
    }

    public function addRecipient(string $email, string $name = ""): void
    {
        try {
            $this->phpMailer->addAddress($email, $name);
        } catch (Exception $e) {
            // TODO: Invalid Address ...
        }
    }

    public function addRecipientCc(string $email, string $name = ""): void
    {
        try {
            $this->phpMailer->addCC($email, $name);
        } catch (Exception $e) {
            // TODO: Invalid Address ...
        }
    }

    public function addRecipientBcc(string $email, string $name = ""): void
    {
        try {
            $this->phpMailer->addBCC($email, $name);
        } catch (Exception $e) {
            // TODO: Invalid Address ...
        }
    }

    public function addRecipients(array $recipients): void
    {
        foreach ($recipients as $email => $name) {
            $this->addRecipient($email, $name);
        }
    }

    public function addReplyTo(string $email, string $name = ""): void
    {
        try {
            $this->phpMailer->addReplyTo($email, $name);
        } catch (Exception $e) {
            // TODO: Invalid Address ...
        }
    }

    /**
     * Adds an attachment from the filesystem. The file will then be accessible for download by the recipients. The
     * given path must point to an existing and accessible file on the filesystem, throws an exception otherwise. If
     * the $filename argument is supplied, this will override the original filename in the mail (so when users download
     * the file, they will have this filename). If you need to add an attachment from a URL, make sure to fetch it
     * beforehand and store it within your filesystem and then use this method.
     *
     * @param string $path
     * @param string|null $filename
     */
    public function addAttachment(string $path, ?string $filename = null): void
    {
        try {
            $attachment = new File($path);
            $this->phpMailer->addAttachment($path, $filename ?? $attachment->getFilename(),
                PHPMailer::ENCODING_BASE64, $attachment->getMimeType());
        } catch (InvalidArgumentException $exception) {
            // TODO: File not exists ...
        } catch (Exception $exception) {
            // TODO: Mailer Exception ...
        }
    }

    /**
     * Adds an embedded (inline) attachment from a file. This can include images, sounds, and just about any other
     * document type. These differ from 'regular' attachments in which they are intended to be displayed inline with
     * the message, not just attached for download. This is used in HTML messages that embed the images the HTML refers
     * to using the `$cid` value in `img` tags, for example `<img src="cid:mylogo">`.
     *
     * @param string $path
     * @param string $cid
     * @param string|null $filename
     */
    public function addInlineAttachment(string $path, string $cid, ?string $filename = null): void
    {
        try {
            $attachment = new File($path);
            $this->phpMailer->addEmbeddedImage($path, $cid, $filename ?? $attachment->getFilename(),
                PHPMailer::ENCODING_BASE64, $attachment->getMimeType());
        } catch (InvalidArgumentException $exception) {
            // TODO: File not exists ...
        } catch (Exception $exception) {
            // TODO: Mailer Exception ...
        }
    }

    /**
     * Adds an attachment from a string or binary attachment (non-filesystem) that will be accessible for download by
     * the recipients. This method can be used to attach ascii or binary data, such as a BLOB record from a database.
     * Filename must be supplied including the file extension. Optionally, the $mimeType can be given for precise
     * content type information for the attachment. Leave null to let the PHPMailer algorithm figure out the proper
     * mime type from the given filename extension.
     *
     * @param string $string
     * @param string $filename
     * @param string|null $mimeType
     */
    public function addBinaryAttachment(string $string, string $filename, ?string $mimeType = null): void
    {
        try {
            $this->phpMailer->addStringAttachment($string, $filename,
                PHPMailer::ENCODING_BASE64, $mimeType ?? '');
        } catch (Exception $exception) {
            // TODO: Mailer Exception ...
        }
    }

    /**
     * Adds an embedded (inline) attachment from a string or binary (non-filesystem) that will be accessible within the
     * email structure (see method addBinaryAttachment).
     *
     * @param string $string
     * @param string $filename
     * @param string|null $mimeType
     * @return void
     */
    public function addBinaryInlineAttachment(string $string, string $filename, ?string $mimeType = null): void
    {
        try {
            $this->phpMailer->addStringAttachment($string, $filename,
                PHPMailer::ENCODING_BASE64, $mimeType ?? '', 'inline');
        } catch (Exception $exception) {
            // TODO: Mailer Exception ...
        }
    }
}
