<?php

namespace Sopinet\ChatBundle\Service;

use FOS\UserBundle\Model\User as User;
use Sopinet\ChatBundle\Entity\ChatRepository;
use Sopinet\ChatBundle\Entity\Message;
use Sopinet\ChatBundle\Entity\Device;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Exception\Exception;
use RMS\PushNotificationsBundle\Exception\InvalidMessageTypeException;
use RMS\PushNotificationsBundle\Message\AndroidMessage;
use RMS\PushNotificationsBundle\Message\iOSMessage;
use Sopinet\ChatBundle\Entity\MessagePackage;

/**
 * Class MessageHelper
 * @package Sopinet\ChatBundle\Service
 */
class MessageHelper {
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Get Message class String from $type
     *
     * @param $type
     * @return null|string
     */
    public function getMessageClassString($type) {
        $em = $this->container->get('doctrine')->getManager();

        $messageClass = "Sopinet\ChatBundle\Entity\Message";

        $cmf = $em->getMetadataFactory();
        $meta = $cmf->getMetadataFor($messageClass);

        $config = $this->container->getParameter('sopinet_chat.config');
        foreach($meta->discriminatorMap as $typeString => $typeClass) {
            if ($typeString == $type) {
                if (!$config['all_type_message'][$type]['interfaceEnabled']) {
                    // Type forbidden, interfaceEnabled for this type is setted in False
                    return;
                } else {
                    return $typeClass;
                }
            }
        }

        if ($config['anyType'] && $type != "") {
            return "Sopinet\ChatBundle\Entity\MessageAny";
        }

        // Type unknow
        return;
    }

    /**
     * Get Message class Object from $type
     *
     * @param $type
     * @return mixed
     * @throw If not found type
     */
    public function getMessageClassObject($type) {
        // Type is obligatory // TODO: Configurate obligatory?
        if ($type == null) throw new Exception("Error Type is null");

        $messageClassString = $this->getMessageClassString($type);
        if ($messageClassString == null) {
            throw new Exception("Error Type");
        }

        $messageClassObject = new $messageClassString;
        if ($messageClassObject->getMyType() == "any") {
            $messageClassObject->setAnyType($type);
            $messageClassObject->setTypeClient($type);
        }

        return $messageClassObject;
    }

    /**
     * Manda un mensaje a los usuarios que el tipo de Mensaje indique
     *
     * @param Message $message
     * @return integer: -1 si hay error, 0 si no hay dispositivos a los que mandar, y más de 0 indicando el número de mensajes enviados
     */
    public function sendMessage(Message $message) {
        $users = $message->getMyDestionationUsers($this->container);
        $sentCount = 0;
        foreach($users as $user) {
            //if($user->getId() != $message->getFromUser()->getId()){
            $sentCount += $this->sendMessageToUser($message, $user);
            //}
        }
        return $sentCount;
    }

    public function sendMessageToUser(Message $message, $user) {
        $config = $this->container->getParameter('sopinet_chat.config');

        $sentCount = 0;
        $em = $this->container->get('doctrine.orm.default_entity_manager');

        if ($user == null) {
            return 0;
        }

        if (is_string($user)) {
            if ($message->getMySenderEmailHas($this->container)) {
                $this->sendMessageToEmail($message, $user);
            }
        } else {
            /** @var Device $device */
            foreach($user->getDevices() as $device) {
                if (($message->getFromDevice() == null || $message->getFromDevice()->getDeviceId() != $device->getDeviceId()) && $device->getState() == '1') {
                    // DEPRECATED! Next code is deprecated, now i pass message object for better iOS options
                    //$messageObject = $message->getMyMessageObject($this->container);
                    //$text = $message;

                    $messagePackage = new MessagePackage();
                    $messagePackage->setMessage($message);
                    $messagePackage->setToDevice($device);
                    $messagePackage->setToUser($user);
                    $messagePackage->setStatus(MessagePackage::STATUS_PENDING);
                    $em->persist($messagePackage);
                    $em->flush();

                    // DO IN BACKGROUND
                    if ($config['background'] && $this->container->get('kernel')->getEnvironment() != 'test') {
                        $msg = array('messagePackageId' => $messagePackage->getId());
                        $this->container->get('old_sound_rabbit_mq.send_message_package_producer')->setContentType('application/json');
                        $this->container->get('old_sound_rabbit_mq.send_message_package_producer')->publish(json_encode($msg));
                        // NO BACKGROUND
                    } else {
                        // TODO: This part should be same that <SEARCH_DUPLICATE>
                        $response = $this->sendRealMessageToDevice($message, $device, $user);
                        if ($response) {
                            $messagePackage->setStatus(MessagePackage::STATUS_OK);
                            if ($message->getMySenderEmailHas($this->container)) {
                                $this->sendMessageToEmail($message, $user);
                            }
                        } else {
                            $messagePackage->setStatus(MessagePackage::STATUS_KO);
                        }
                        if ($device->getDeviceType() == Device::TYPE_ANDROID) {
                            $messagePackage->setProcessed(true); // Yes, processed
                        } elseif (
                            $device->getDeviceType() == Device::TYPE_IOS ||
                            $device->getDeviceType() == Device::TYPE_VIRTUALNONE
                        ) {
                            $messagePackage->setProcessed(false); // Not processed
                        }
                        $em->persist($messagePackage);
                        $em->flush();
                        // TODO: End <SEARCH_DUPLICATE>
                    }

                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    /**
     * Send message to User and device
     * It functions transform to Real message data array
     *
     * @param Object $msg
     * @param String $to
     *
     */
    public function sendRealMessageToDevice(Message $message, Device $device, $user = null, Request $request = null, $printOut = false)
    {
        $config = $this->container->getParameter('sopinet_chat.config');

        $messageData = $message->getMyMessageObject($this->container, $request);

        $text = $message->__toString();

        $messageArray = array();

        $vars = get_object_vars($messageData);

        foreach($vars as $key => $value) {
            $messageArray[$key] = $value;
        }

        if ($message->getChat() != null) {
            $em = $this->container->get('doctrine.orm.default_entity_manager');

            /** @var ChatRepository $repositoryChat */
            $repositoryChat = $em->getRepository('SopinetChatBundle:Chat');
            $repositoryChat->enabledChat($message->getChat());

            $chatData = $message->getChat()->getMyAddMessageObject($this->container);
            $varsChat = get_object_vars($chatData);
            foreach($varsChat as $key => $value) {
                $messageArray[$key] = $value;
            }
        }

        if ($user != null) {
            $messageArray['toUserId'] = $user->getId();
        }

        if ($printOut) {
            ld($messageArray);
            //echo "Array Data Message: " . serialize($messageArray);
        }

        if ($device->getDeviceType() == Device::TYPE_ANDROID && $config['enabledAndroid']) {
            return $this->sendGCMessage($messageArray, $text, $device->getDeviceGCMId());
        } elseif ($device->getDeviceType() == Device::TYPE_IOS && $config['enabledIOS']) {
            return $this->sendAPNMessage($messageArray, $text, $device->getDeviceId(), $message->getMyIOSContentAvailable(), $message->getMyIOSNotificationFields());
        } elseif ($device->getDeviceType() == Device::TYPE_WEB && $config['enabledWeb']) {
            return $this->sendWebMessage($messageArray, $text, $device->getDeviceId());
        } elseif ($device->getDeviceType() == Device::TYPE_VIRTUALNONE) {
            // Do Nothing, but return true, so, state = 1, it is "sent" (in virtualnone: saved)
            return true;
        }
    }

    /**
     * Función que envía un mensaje a un ID de sesión de una web
     *
     * @param $mes
     * @param $text
     * @param $to
     */
    private function sendWebMessage($mes, $text, $to) {
        // Esto no haría falta :S
        $mes['text'] = $text;

        $pusher = $this->container->get('gos_web_socket.wamp.pusher');
        $pusher->push($mes, 'session_topic', ['idSession' => $to]);

        /*
        $pusher->push([
            'data' => $data,
            'text' => $text
        ], 'session_topic', ['idSession' => $to]);
        */

        return true;
    }

    /**
     * Funcion que envia un mensaje con el servicio GCM de Google
     * @param Array $mes
     * @param DeviceId $to
     */
    private function sendGCMessage($mes, $text, $to)
    {
        $message=new AndroidMessage();
        $message->setMessage($text);
        $message->setData($mes);
        $message->setDeviceIdentifier($to);
        $message->setGCM(true);
//        $logger = $this->container->get('logger');
//        $logger->emerg(implode(',', $message->getData()));
        try {
            $response = $this->container->get('rms_push_notifications')->send($message);
        } catch (InvalidMessageTypeException $e) {
            throw $e;
        }

        return $response;
    }


    /**
     * Funcion que envia un mensaje con el sevricio APN de Apple
     * @param Array $mes, Array of parameters, it should have 'type'
     * @param String $text
     * @param String $to
     * @param boolean $wakeUp - boolean parameter, if true: it send normal message, if false it send notification message.
     * It shoud be replace by typemessage
     *
     * @throws \InvalidArgumentException
     */
    private function sendAPNMessage($mes, $text, $to, $contentAvailable, $notificationFields)
    {
        $message=new iOSMessage();
        try {
            $message->setData($mes);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        }
        $alert=[];
//        $logger = $this->container->get('logger');
//        $logger->emerg(implode(',', $mes));

        // - $em = $this->container->get("doctrine.orm.entity_manager");
        // - $reDevice = $em->getRepository('ApplicationSopinetUserBundle:User');

        /** @var $user */
        // $user=$reDevice->findOneByPhone($mes['phone']);
        if ($contentAvailable) {
            //nombredelChat@nombredeUsuario;
            $messageString = $notificationFields;
            $alert['loc-args'] = array($messageString, $text);
            $alert['loc-key']=$mes['type'];
            $message->setMessage($alert);
            $config = $this->container->getParameter('sopinet_chat.config');
            $message->setAPSSound($config['soundIOS']);
            $message->apsBody["aps"]["badge"] = "increment";

        }
        $message->setDeviceIdentifier($to);
        $message->setAPSContentAvailable($contentAvailable);
        return $this->container->get('rms_push_notifications')->send($message);
    }

    /**
     * Send a message to Email
     *
     * @return bool
     */
    public function sendMessageToEmail(Message $message, $user) {
        if (is_object($user)) {
            $toEmail = $user->getEmail();
        } else {
            $toEmail = $user;
        }

        if ($this->container->hasParameter('mailer_email')) {
            $mailer_email = $this->container->getParameter('mailer_email');
        } else if ($this->container->hasParameter('mailer_user')) {
            $mailer_email = $this->container->getParameter('mailer_user');
        } else {
            $mailer_email = "define@youremail.com";
        }
        $emailMessage = \Swift_Message::newInstance()
            ->setSubject($message->getMySenderEmailSubject($this->container, $user))
            ->setFrom($mailer_email)
            ->setTo($toEmail)
            ->setBody(
                $message->getMySenderEmailBody($this->container, $user),
                'text/html'
            )
        ;

        $senderAttach = $message->getMySenderEmailAttach($this->container, $user);
        if ($senderAttach != null) {
            if (is_array($senderAttach)) {
                foreach($senderAttach as $attach) {
                    $emailMessage->attach(\Swift_Attachment::fromPath($attach));
                }
            } else {
                $emailMessage->attach(\Swift_Attachment::fromPath($senderAttach));
            }
        }

        $this->container->get('mailer')->send($emailMessage);

        return true;
    }
}
