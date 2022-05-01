<?php
require_once 'vendor/autoload.php';
require_once 'Exceptions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailSender {
    private PHPMailer $mailer;
    function __construct($smtphost = Config::SMTP_SERV, $user = Config::SMTP_USR, $username = Config::SMTP_USRNAME,
                         $password = Config::SMTP_PWD, $debug = SMTP::DEBUG_OFF) {
        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->SMTPDebug = $debug;
        $mail->isSMTP();
        $mail->Host = $smtphost;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $password;
        try {
            $mail->setFrom($user, $username);
        } catch (Exception $e) {
            throw new MailSendFailedException($e->getMessage(), $e->getCode(), $e);
        }
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $this->mailer = $mail;
    }
    public function addAddress($address): MailSender {
        try {
            $this->mailer->addAddress($address);
        } catch (Exception $e) {
            throw new MailSendFailedException($e->getMessage(), $e->getCode(), $e);
        }
        return $this;
    }
    public function setSubject($subject): MailSender {
        $this->mailer->Subject = $subject;
        return $this;
    }
    public function setBody($body): MailSender {
        $this->mailer->Body = $body;
        return $this;
    }
    public function setAltBody($body): MailSender {
        $this->mailer->AltBody = $body;
        return $this;
    }
    public function setHTML(): MailSender {
        $this->mailer->isHTML(true);
        return $this;
    }
    public function send(): void {
        try {
            $this->mailer->send();
        } catch (Exception $e) {
            throw new MailSendFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
