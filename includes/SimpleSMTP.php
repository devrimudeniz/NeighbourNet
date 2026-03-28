<?php
/**
 * SimpleSMTP - A lightweight PHP SMTP class for sending emails via SSL/TLS
 */
class SimpleSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $timeout = 30;
    private $debug = false;

    public function __construct($host, $port, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function send($to, $subject, $body, $fromName = 'Kalkan Social') {
        $socket = fsockopen("ssl://{$this->host}", $this->port, $errno, $errstr, $this->timeout);
        if (!$socket) {
            error_log("SMTP Connect Error: $errstr ($errno)");
            return false;
        }

        // Helper to read server response
        $getP = function() use ($socket) {
            $s = '';
            while ($str = fgets($socket, 515)) {
                $s .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $s;
        };

        // Helper to send command
        $putC = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $getP(); // Welcome message

        $putC("EHLO " . $_SERVER['SERVER_NAME']);
        $getP();

        $putC("AUTH LOGIN");
        $getP();
        $putC(base64_encode($this->username));
        $getP();
        $putC(base64_encode($this->password));
        $res = $getP();

        if (strpos($res, '235') === false) { // Auth failed
            error_log("SMTP Auth Failed: $res");
            return false;
        }

        $putC("MAIL FROM: <{$this->username}>");
        $getP();

        $putC("RCPT TO: <$to>");
        $getP();

        $putC("DATA");
        $getP();

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$this->username}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date("r") . "\r\n";
        $headers .= "X-Mailer: SimpleSMTP/1.0.0";

        $encodedBody = chunk_split(base64_encode($body));

        $putC($headers . "\r\n\r\n" . $encodedBody . "\r\n.");
        $res = $getP();

        $putC("QUIT");
        fclose($socket);

        return strpos($res, '250') !== false;
    }
}
?>
