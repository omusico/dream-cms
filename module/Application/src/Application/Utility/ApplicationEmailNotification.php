<?php
namespace Application\Utility;

use Application\Service\ApplicationSetting as SettingService;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Application\Event\ApplicationEvent;
use User\Model\UserBase as UserBaseModel;
use Exception;
use Application\Utility\ApplicationErrorLogger;

class ApplicationEmailNotification
{
    /**
     * Send a notification
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param array $replacements
     *      array find
     *      array replace
     * @param string $replaceLeftDevider
     * @param string $replaceRightDevider
     * @return boolean
     */
    public static function sendNotification($email, $subject, $message, array $replacements = [], $replaceLeftDevider = '__', $replaceRightDevider = '__')
    {
        try {
            // fire the send email notification event
            $result = ApplicationEvent::fireSendEmailNotificationEvent($email, $subject);

            if ($result->stopped()) {
                return false;
            }

            // replace special markers
            if (isset($replacements['find'], $replacements['replace'])) {
                // process replacements
                $replacements['find'] = array_map(function($value) use($replaceLeftDevider, $replaceRightDevider) {
                    return $replaceLeftDevider . $value . $replaceRightDevider;
                }, $replacements['find']);
         
                $message = str_replace($replacements['find'], $replacements['replace'], $message);
            }

            // add the mime type
            $message = new MimePart($message);
            $message->type = 'text/html';

            $body = new MimeMessage();
            $body->setParts([$message]);

            $messageInstance = new Message();
            $messageInstance->addFrom(SettingService::getSetting('application_notification_from'))
                ->addTo($email)
                ->setSubject($subject)
                ->setBody($body)
                ->setEncoding('UTF-8');

            // should we use SMTP?
            if ((int) SettingService::getSetting('application_use_smtp')) {
                $transport = new SmtpTransport();
                $options = new SmtpOptions([
                    'host' => SettingService::getSetting('application_smtp_host'),
                    'connection_class' => 'login',
                    'connection_config' => [
                        'ssl' => 'tls',
                        'username' => SettingService::getSetting('application_smtp_user'),
                        'password' => SettingService::getSetting('application_smtp_password')
                    ],
                    'port' => SettingService::getSetting('application_smtp_port'),
                ]);

                $transport->setOptions($options);
            }
            else {
                $transport = new SendmailTransport();
            }

            $transport->send($messageInstance);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
            return false;
        }

        return true;
    }
}