<?php

namespace Sopinet\ChatBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Sopinet\ChatBundle\Entity\Chat;
use Sopinet\ChatBundle\Entity\Device;
use Sopinet\ChatBundle\Entity\Message;
use Sopinet\ChatBundle\Entity\MessagePackage;
use Sopinet\ChatBundle\Entity\MessageText;
use SopinetApiHelperBundle\Services\ApiHelper;
use Sopinet\ChatBundle\Service\InterfaceHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Exception\Exception;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ChatApiController extends FOSRestController{
    /**
     * @Get("debug")
     */
    public function debugAction() {
        die("debugAction");
        $chatHelper = $this->get('sopinet_chatbundle_messagehelper');

        /** @var Message $messageClassObject */
        $messageClassObject = $chatHelper->getMessageClassObject('imageadsf');

        //ldd($messageClassObject);


        $config = $this->container->getParameter('sopinet_chat.config');

        $em = $this->getDoctrine()->getEntityManager();

        $reMess = $em->getRepository("SopinetChatBundle:Message");
        $mess = $reMess->find(0);
        ldd($mess->getMyMessageObject());

        $messageNew = new MessageText();
        $messageNew->setId(142);
        //$messageNew->setProbandoImage("holaMundo");
        $messageNew->setFromTime(new \DateTime());

        $messageNew->getMyType();

        $em->persist($messageNew);
        $em->flush();

        ldd($messageNew);

        //$message =
        //ldd($config);
        //ldd("llega");
    }

    /**
     *
     * @ApiDoc(
     *   description="Recibe un mensaje de Chat desde un dispositivo y es enviado a todos los dispositivos de ese Chat.",
     *   section="SopinetChat",
     *   parameters={
     *      {"name"="text", "dataType"="string", "required"=true, "description"="Texto de mensaje en el chat."},
     *      {"name"="type", "dataType"="string", "required"=true, "description"="Tipo de mensaje."},
     *      {"name"="fromDevice", "dataType"="string", "required"=true, "Device que lo envía."},
     *      {"name"="chat", "dataType"="string", "required"=true, "description"="ID del Chat en el servidor en el que se envía el mensaje"},
     *      {"name"="id", "dataType"="string", "required"=true, "description"="ID del Mensaje"},
     *      {"name"="fromTime", "dataType"="string", "required"=true, "description"="Fecha y hora de envío de mensaje, formato Timestamp"}
     *   }
     * )
     *
     * @Post("sendMessage")
     */
    public function sendMessageAction(Request $request)
    {
        /** @var InterfaceHelper $interfaceHelper */
        $interfaceHelper = $this->get('sopinet_chatbundle_interfacehelper');

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->get('sopinet_apihelperbundle_apihelper');

        try {
            $message = $interfaceHelper->sendMessage($request);
        } catch (Exception $e) {
            return $apiHelper->responseDenied($e->getMessage());
        }

        return $apiHelper->responseOk($message, Message::GROUP_CREATE);
    }

    /**
     *
     * @ApiDoc(
     *   description="Crea un Chat entre 1 o más usuarios.",
     *   section="SopinetChat",
     *   parameters={
     *      {"name"="name", "dataType"="string", "required"=true, "description"="Nombre del Chat."},
     *      {"name"="chatMembers", "dataType"="string", "required"=true, "description"="IDs de Usuarios que pertenecen al Chat, separados por comas."}
     *   }
     * )
     *
     * @Post("createChat")
     */
    public function createChatAction(Request $request) {
        /** @var InterfaceHelper $interfaceHelper */
        $interfaceHelper = $this->get('sopinet_chatbundle_interfacehelper');

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->get('sopinet_apihelperbundle_apihelper');

        try {
            $chat = $interfaceHelper->createChat($request);
        } catch (Exception $e) {
            return $apiHelper->responseDenied($e->getMessage());
        }

        return $apiHelper->responseOk($chat, Chat::GROUP_CREATE);
    }

    /**
     * @ApiDoc(
     *   description="Register Device in ChatBundle.",
     *   section="SopinetChat",
     *   parameters={
     *      {"name"="deviceId", "dataType"="string", "required"=true, "description"="Texto de mensaje en el chat."},
     *      {"name"="deviceType", "dataType"="string", "required"=true, "description"="Tipo de mensaje."}
     *   }
     * )
     *
     * @Post("registerDevice")
     */
    public function registerDeviceAction(Request $request)
    {
        /** @var InterfaceHelper $interfaceHelper */
        $interfaceHelper = $this->get('sopinet_chatbundle_interfacehelper');
        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->get('sopinet_apihelperbundle_apihelper');

        try {
            $device = $interfaceHelper->addDevice($request);
        } catch (Exception $e) {
            return $apiHelper->responseDenied($e->getMessage());
        }

        return $apiHelper->responseOk($device, Device::GROUP_CREATE);
    }

    /**
     *
     * @ApiDoc(
     *   description="send unproccesed Notifications. (add User parameters)",
     *   section="SopinetChat",
     *   parameters={
     *      {"name"="deviceId", "dataType"="string", "required"=true, "description"="Device ID."}
     *   }
     * )
     *
     * @Post("sendUnprocessNotification")
     */
    public function sendUnprocessNotificationAction(Request $request) {
        /** @var InterfaceHelper $interfaceHelper */
        $interfaceHelper = $this->get('sopinet_chatbundle_interfacehelper');

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->get('sopinet_apihelperbundle_apihelper');

        try {
            $count = $interfaceHelper->sendUnprocessNotification($request);
        } catch (Exception $e) {
            return $apiHelper->responseDenied($e->getMessage());
        }

        return $apiHelper->responseOk($count);
    }

    /**
     *
     * @ApiDoc(
     *   description="clean unproccesed Notification. (add User parameteres)",
     *   section="SopinetChat",
     *   parameters={
     *      {"name"="deviceId", "dataType"="string", "required"=true, "description"="Device ID."},
     *      {"name"="messageId", "dataType"="string", "required"=true, "description"="Message ID."}
     *   }
     * )
     *
     * @Post("cleanUnprocessNotification")
     */
    public function cleanUnprocessNotificationAction(Request $request) {
        /** @var InterfaceHelper $interfaceHelper */
        $interfaceHelper = $this->get('sopinet_chatbundle_interfacehelper');

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->get('sopinet_apihelperbundle_apihelper');

        try {
            $messagePackage = $interfaceHelper->cleanUnprocessNotification($request);
        } catch (Exception $e) {
            return $apiHelper->responseDenied($e->getMessage());
        }

        return $apiHelper->responseOk($messagePackage, MessagePackage::GROUP_CREATE);
    }

    /**
     *
     * @ApiDoc(
     *   description="Clean unread messages.",
     *   section="SopinetChat",
     *   parameters={
     *      {"name"="email", "dataType"="string", "required"=false, "description"="Correo electrónico del usuario."},
     *      {"name"="password", "dataType"="string", "required"=false, "description"="Contraseña del usuario (en texto plano sin codificar)."},
     *      {"name"="chat", "dataType"="string", "required"=true, "description"="ID del Chat en el servidor en el que se envía el mensaje"}
     *   }
     * )
     *
     * @Post("cleanUnreadMessages")
     */
    public function cleanUnreadMessagesAction(Request $request) {
        /** @var InterfaceHelper $interfaceHelper */
        $interfaceHelper = $this->get('sopinet_chatbundle_interfacehelper');

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->get('sopinet_apihelperbundle_apihelper');

        try {
            $chat = $interfaceHelper->cleanUnreadMessages($request);
        } catch (Exception $e) {
            return $apiHelper->responseDenied($e->getMessage());
        }

        return $apiHelper->responseOk($chat, Chat::GROUP_CLEAN);
    }
    
    /**
     *
     * @ApiDoc(
     *   description="Funcion para obtener nuestros chats.",
     *   section="SopinetChat",
     *   parameters={
     *      {"name"="email", "dataType"="string", "required"=false, "description"="Correo electrónico del usuario."},
     *      {"name"="password", "dataType"="string", "required"=false, "description"="Contraseña del usuario (en texto plano sin codificar)."},
     *   }
     * )
     *
     * @Post("chats")
     */
    public function chatsAction(Request $request) {

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->get('sopinet_apihelperbundle_apihelper');

        /** @var ChatHelper $chatHelper */
        $chatHelper = $this->get('sopinet_chatbundle_chathelper');

        $chats = $chatHelper->getMyChats($request);

        return $apiHelper->responseOk($chats, Chat::GROUP_CREATE);
    }
}
