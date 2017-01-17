<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

namespace Service;

use \Exception;

class Smtp
{
    /**
     * smtp socket
     */
    protected $smtp;

    /**
     * smtp server
     */
    protected $host;

    /**
     * smtp server port
     */
    protected $port;

    /**
     * smtp secure ssl tls
     */
    protected $secure;

    /**
     * EHLO message
     */
    protected $ehlo;

    /**
     * smtp username
     */
    protected $username;

    /**
     * smtp password
     */
    protected $password;

    /**
     * $this->CRLF
     *
     * @var string
     */
    protected $CRLF = "\r\n";

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var LoggerInterface - Used to make things prettier than self::$logger
     */
    // protected $logger;

    /**
     * Stack of all commands issued to SMTP
     *
     * @var array
     */
    protected $commandStack = array();

    /**
     * Stack of all results issued to SMTP
     *
     * @var array
     */
    protected $resultStack = array();

    public function __construct()
    {
        // $this->logger = $logger;
    }

    /**
     * set server and port
     *
     * @param string $host   server
     * @param int    $port   port
     * @param string $secure ssl tls
     *
     * @return $this
     */
    public function setServer($host, $port, $secure=null)
    {
        $this->host                  = $host;
        $this->port                  = $port;
        $this->secure                = $secure;
        if (!$this->ehlo) {
            $this->ehlo = $host;
        }
        // $this->logger && $this->logger->debug("Set: the server");
        return $this;
    }

    /**
     * auth with server
     *
     * @param string $username
     * @param string $password
     *
     * @return $this
     */
    public function setAuth($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        // $this->logger && $this->logger->debug("Set: the auth");
        return $this;
    }

    /**
     * set the EHLO message
     *
     * @param $ehlo
     *
     * @return $this
     */
    public function setEhlo($ehlo)
    {
        $this->ehlo = $ehlo;
        return $this;
    }

    /**
     * Send the message
     *
     * @param Message $message
     *
     * @throws Exception
     * @throws Exception
     * @throws Exception
     *
     * @return bool
     */
    public function send(Message $message)
    {
        // $this->logger && $this->logger->debug('Set: a message will be sent');
        $this->message = $message;
        $this->connect()
            ->ehlo();

        if ($this->secure === 'tls') {
            $this->starttls()
                ->ehlo();
        }
        $this->authLogin()
            ->mailFrom()
            ->rcptTo()
            ->data()
            ->quit();
        return fclose($this->smtp);
    }

    /**
     * connect the server
     * SUCCESS 220
     *
     * @throws Exception
     * @throws Exception
     *
     * @return $this
     */
    protected function connect()
    {
        // $this->logger && $this->logger->debug("Connecting to {$this->host} at {$this->port}");
        $host       = ($this->secure == 'ssl') ? 'ssl://'.$this->host : $this->host;
        $this->smtp = @fsockopen($host, $this->port);
        //set block mode
        //    stream_set_blocking($this->smtp, 1);
        if (!$this->smtp) {
            throw new Exception('Could not open SMTP Port.');
        }
        $code = $this->getCode();
        if ($code !== '220') {
            throw new Exception('220'.$code.array_pop($this->resultStack));
        }
        return $this;
    }

    /**
     * SMTP STARTTLS
     * SUCCESS 220
     *
     * @throws Exception
     * @throws Exception
     * @throws Exception
     *
     * @return $this
     */
    protected function starttls()
    {
        $in   = 'STARTTLS'.$this->CRLF;
        $code = $this->pushStack($in);
        if ($code !== '220') {
            throw new Exception('220'.$code.array_pop($this->resultStack));
        }
        if (!\stream_socket_enable_crypto($this->smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception('Start TLS failed to enable crypto');
        }
        return $this;
    }

    /**
     * SMTP EHLO
     * SUCCESS 250
     *
     * @throws Exception
     * @throws Exception
     *
     * @return $this
     */
    protected function ehlo()
    {
        $in   = 'EHLO '.$this->ehlo.$this->CRLF;
        $code = $this->pushStack($in);
        if ($code !== '250') {
            throw new Exception('250'.$code.array_pop($this->resultStack));
        }
        return $this;
    }

    /**
     * SMTP AUTH LOGIN
     * SUCCESS 334
     * SUCCESS 334
     * SUCCESS 235
     *
     * @throws Exception
     * @throws Exception
     *
     * @return $this
     */
    protected function authLogin()
    {
        if ($this->username === null && $this->password === null) {
            // Unless the user has specifically set a username/password
            // Do not try to authorize.
            return $this;
        }

        $in   = 'AUTH LOGIN'.$this->CRLF;
        $code = $this->pushStack($in);
        if ($code !== '334') {
            throw new Exception('334'.$code.array_pop($this->resultStack));
        }
        $in   = base64_encode($this->username).$this->CRLF;
        $code = $this->pushStack($in);
        if ($code !== '334') {
            throw new Exception('334'.$code.array_pop($this->resultStack));
        }
        $in   = base64_encode($this->password).$this->CRLF;
        $code = $this->pushStack($in);
        if ($code !== '235') {
            throw new Exception('235'.$code.array_pop($this->resultStack));
        }
        return $this;
    }

    /**
     * SMTP MAIL FROM
     * SUCCESS 250
     *
     * @throws Exception
     * @throws Exception
     *
     * @return $this
     */
    protected function mailFrom()
    {
        $in   = "MAIL FROM:<{$this->message->getFromEmail()}>".$this->CRLF;
        $code = $this->pushStack($in);
        if ($code !== '250') {
            throw new Exception('250'.$code.array_pop($this->resultStack));
        }
        return $this;
    }

    /**
     * SMTP RCPT TO
     * SUCCESS 250
     *
     * @throws Exception
     * @throws Exception
     *
     * @return $this
     */
    protected function rcptTo()
    {
        foreach ($this->message->getTo() as $toEmail) {
            $in   = 'RCPT TO:<'.$toEmail.'>'.$this->CRLF;
            $code = $this->pushStack($in);
            if ($code !== '250') {
                throw new Exception('250'.$code.']'.array_pop($this->resultStack));
            }
        }
        return $this;
    }

    /**
     * SMTP DATA
     * SUCCESS 354
     * SUCCESS 250
     *
     * @throws Exception
     * @throws Exception
     *
     * @return $this
     */
    protected function data()
    {
        $in   = 'DATA'.$this->CRLF;
        $code = $this->pushStack($in);
        if ($code !== '354') {
            throw new Exception('354'.$code.']'.array_pop($this->resultStack));
        }
        $in   = $this->message->toString();
        $code = $this->pushStack($in);
        if ($code !== '250') {
            throw new Exception('250'.$code.']'.array_pop($this->resultStack));
        }
        return $this;
    }

    /**
     * SMTP QUIT
     * SUCCESS 221
     *
     * @throws Exception
     * @throws Exception
     *
     * @return $this
     */
    protected function quit()
    {
        $in   = 'QUIT'.$this->CRLF;
        $code = $this->pushStack($in);
        if ($code !== '221') {
            throw new Exception('221'.$code.']'.array_pop($this->resultStack));
        }
        return $this;
    }

    protected function pushStack($string)
    {
        $this->commandStack[] = $string;
        fwrite($this->smtp, $string, strlen($string));
        // $this->logger && $this->logger->debug('Sent: '. $string);
        return $this->getCode();
    }

    /**
     * get smtp response code
     * once time has three digital and a space
     *
     * @throws Exception
     *
     * @return string
     */
    protected function getCode()
    {
        while ($str = fgets($this->smtp, 515)) {
            // $this->logger && $this->logger->debug("Got: ". $str);
            $this->resultStack[] = $str;
            if (substr($str, 3, 1) == ' ') {
                $code = substr($str, 0, 3);
                return $code;
            }
        }
        throw new Exception('SMTP Server did not respond with anything I recognized');
    }
}
