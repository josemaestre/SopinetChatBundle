<?php

namespace Sopinet\ChatBundle\Service;

use AppBundle\Services\LoginHelper;
use Sopinet\ChatBundle\Entity\Chat;
use Sopinet\ChatBundle\Entity\ChatRepository;
use Sopinet\ChatBundle\Entity\DeviceRepository;
use Sopinet\ChatBundle\Entity\Message;
use Sopinet\ChatBundle\Entity\MessagePackage;
use Sopinet\ChatBundle\Entity\MessageUserState;
use Sopinet\ChatBundle\Entity\UserState;
use Sopinet\ChatBundle\Form\DeviceType;
use Sopinet\ChatBundle\Entity\Device;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Exception\Exception;
use SopinetApiHelperBundle\Services\ApiHelper;

/**
 * Class InterfaceHelper
 * @package Sopinet\ChatBundle\Service
 */
class InterfaceHelper
{
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Manda un mensaje a un Chat
     *
     * request llevará la información pertinente
     *      text - Texto de mensaje en el chat.
     *      type - Tipo de mensaje.
     *      fromDevice - Device que lo envía.
     *      chat - ID del Chat en el servidor en el que se envía el mensaje,
     *      id - ID del Mensaje,
     *      fromTime - Fecha y hora de envío de mensaje, formato Timestamp
     *
     * @param Request $request
     */
    public function sendMessage(Request $request) {
        /** @var MessageHelper $messageHelper */
        $messageHelper = $this->container->get('sopinet_chatbundle_messagehelper');

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->container->get('sopinet_apihelperbundle_apihelper');

        /** @var Message $messageClassObject */
        try {
            $messageClassObject = $messageHelper->getMessageClassObject($request->get('type'));
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        $formClassString = $messageClassObject->getMyForm();

        $formClassObject = new $formClassString($this->container, $request);
        $form = $this->container->get('form.factory')->create($formClassObject, $messageClassObject);

        /** @var Form $form */
        $form = $apiHelper->handleForm($request, $form);

        // TODO: Añadir comprobación en el FORM de que el Usuario pertenece al Chat indicado y de que dispone
        // es el dueño del Device que se ha especificado.
        if ($form->isValid()) {
            $em=$this->container->get('doctrine.orm.default_entity_manager');

            $em->persist($messageClassObject);
            $em->flush();

            // Send Message
            $messageHelper->sendMessage($messageClassObject);

            return $messageClassObject;
        } else {
            throw new Exception($form->getErrorsAsString());
        }
    }

    /**
     * Create Chat from Users list
     * name - Chat name
     * type - Type
     * chatMembers - Users id, separated by commas
     * (anothers parameters)
     *
     * @param Request $request
     * @return Chat $chat
     */
    public function createChat(Request $request) {
        // TODO: Cómo garantizar que los usuarios a los que se añade a la sala de Chat, aprueban su incorporación
        // TODO: ¿Comprobar que el Admin va en chatMembers?
        // TODO: Comprobar que todos los usuarios existen y si no devolver error

        /** @var ChatHelper $chatHelper */
        $chatHelper = $this->container->get('sopinet_chatbundle_chathelper');

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->container->get('sopinet_apihelperbundle_apihelper');

        /** @var Chat $chatClassObject */
        try {
            $chatClassObject = $chatHelper->getChatClassObject($request->get('type'));
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        try {
            $chatExist = $chatClassObject->getMyChatExist($this->container, $request);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        if ($chatExist){
            return $chatExist;
        }

        $formClassString = $chatClassObject->getMyForm();
        $formClassObject = new $formClassString($this->container, $request);
        $form = $this->container->get('form.factory')->create($formClassObject, $chatClassObject);

        /** @var Form $form */
        // $form = $apiHelper->handleForm($request, $form);

        //$chat = new Chat();
        //$form = $this->container->get('form.factory')->create(new ChatType($this->container, $request), $chat);

        /** @var Form $form */
        try {
            $form = $apiHelper->handleForm($request, $form);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        if ($form->isValid()) {
            $em=$this->container->get('doctrine.orm.default_entity_manager');
            $em->persist($chatClassObject);
            $em->flush();

            return $chatClassObject;
        } else {
            throw new Exception($apiHelper->getFormErrors($form->getErrorsAsString()));
        }
    }

    public function addDevice(Request $request) {
        // TODO: Configurar para que sólo funcione con un dispositivo por Usuario, o por varios.

        $em = $this->container->get('doctrine.orm.default_entity_manager');

        /** @var DeviceRepository $reDevice */
        $reDevice = $em->getRepository("SopinetChatBundle:Device");
        if (!$reDevice->existsDevice($request->get('deviceId'))) {
            $device = new Device();
        } else {
            $device = $reDevice->findOneByDeviceId($request->get('deviceId'));
            /**
             * Deprecated!, este código se podría eliminar
            if ($request->get('deviceType') == Device::TYPE_IOS) {
            return null;
            } elseif ($request->get('deviceType') == Device::TYPE_ANDROID) {
            $device = $reDevice->findOneByDeviceId($request->get('deviceId'));
            }
             **/
        }

        /** @var Form $form */
        $form = $this->container->get('form.factory')->create(new DeviceType($this->container, $request, $device), $device);
        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->container->get('sopinet_apihelperbundle_apihelper');
        $form = $apiHelper->handleForm($request, $form);
        // Comprobar que TYPE sólo sea iOS o Android
        if ($form->isValid()) {
            $em=$this->container->get('doctrine.orm.default_entity_manager');
            $em->persist($device);
            $em->flush();
        } else {
            return false;
            // throw new Exception("Form invalid");
        }

        return $device;
    }

    // Notificará el login en el sistema, reenviando todos los mensajes pendientes
    /**
     * Send unprocessed notifications to logged User and device
     * - Logged User (toUser)
     * - DeviceID (toDevice)
     *
     * @param Request $request
     */
    public function sendUnprocessNotification(Request $request) {
        // Get toUser
        /** @var LoginHelper $loginHelper */
        $loginHelper = $this->container->get('sopinet_login_helper');
        /** @var $user */
        try {
            $user = $loginHelper->getUser($request);
        } catch(Exception $e) {
            throw new Exception('User is required');
        }

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.default_entity_manager');

        // Get toDevice
        $reDevice = $em->getRepository("SopinetChatBundle:Device");
        /** @var Device $device */
        $device = $reDevice->findOneByDeviceId($request->get('deviceId'));
        if ($device == null) throw new Exception('Device is not valid');
        $isDevice = false;
        /** @var Device $dev */
        foreach($user->getDevices() as $dev) {
            if ($dev->getDeviceId() == $device->getDeviceId()) $isDevice = true;
        }
        if (!$isDevice) throw new Exception('Device is not valid');

        // Get MessagePackage
        $reMessagePackage = $em->getRepository("SopinetChatBundle:MessagePackage");
        $messagesPackage = $reMessagePackage->findBy(array(
            'toUser' => $user->getId(),
            'toDevice' => $device->getDeviceId(),
            'processed' => false
        ));

        /** @var MessageHelper $messageHelper */
        $messageHelper = $this->container->get('sopinet_chatbundle_messagehelper');

        $i = 0;
        /** @var MessagePackage $messagePackage */
        foreach($messagesPackage as $messagePackage) {
            $isOk = $messageHelper->sendRealMessageToDevice(
                $messagePackage->getMessage(),
                $messagePackage->getToDevice(),
                $messagePackage->getToUser()
            );
            if ($isOk) $i++;
        }

        return $i;
    }

    // Limpiará una notificación del sistema
    /**
     * Clear unprocessed notification to logged User and device
     * - Logged User (toUser)
     * - DeviceId (toDevice)
     * - messageId (unique identifiquier)
     *
     * @param Request $request
     */
    public function cleanUnprocessNotification(Request $request) {
        // Get toUser
        /** @var LoginHelper $loginHelper */
        $loginHelper = $this->container->get('sopinet_login_helper');
        /** @var $user */
        try {
            $user = $loginHelper->getUser($request);
        } catch(Exception $e) {
            throw new Exception('User is required');
        }

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.default_entity_manager');

        // Get toDevice
        $reDevice = $em->getRepository("SopinetChatBundle:Device");
        /** @var Device $device */
        $device = $reDevice->findOneByDeviceId($request->get('deviceId'));
        if ($device == null) throw new Exception('Device is not valid');
        $isDevice = false;
        /** @var Device $dev */
        foreach($user->getDevices() as $dev) {
            if ($dev->getDeviceId() == $device->getDeviceId()) $isDevice = true;
        }
        if (!$isDevice) throw new Exception('Device is not valid');

        // Get MessagePackage
        $reMessagePackage = $em->getRepository("SopinetChatBundle:MessagePackage");
        /** @var MessagePackage $messagePackage */
        $messagePackage = $reMessagePackage->findOneBy(array(
            'toUser' => $user->getId(),
            'toDevice' => $device->getDeviceId(),
            'message' => $request->get('messageId')
        ));

        $messagePackage->setProcessed(true);

        $em->persist($messagePackage);
        $em->flush();

        return $messagePackage;
    }

    /**
     * Hará un ping en el sistema de manera que se guarde la última hora en la cual
     * el usuario hizo una petición, así como su estado: Conectado
     * En caso de cambiar de estado, se notificará a los demás usuarios del Chat en el que está
     * de que el estado a cambiado a conectado.
     *
     * @param $user - Usuario sobre el que se hace el ping
     * @return boolean $notify - Devuelve si se notifica (hay cambio), o no.
     */
    public function doPing($user) {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $notify = false;
        $userState = $user->getUserState();

        // Si no existe el registro, se crea
        if ($userState == null) {
            $userState = new UserState();
            $userState->setUser($user);
            $user->setUserState($userState);
            $em->persist($user);
            $em->flush();
            $notify = true;
        } else {
            if ($userState->getState() == UserState::STATE_DISCONNECTED) {
                // Notificar que se vuelve activo
                $notify = true;
            }
        }

        // TODO: Check updateAt?
        $userState->setState(UserState::STATE_CONNECTED);
        $em->persist($userState);
        $em->flush();

        if ($notify) {
            $message = new MessageUserState();
            $message->setFromUser($user);
            $message->setText(UserState::STATE_CONNECTED);
            $message->setFromTime(new \DateTime());
            /** @var MessageHelper $messageHelper */
            $messageHelper = $this->container->get('sopinet_chatbundle_messagehelper');
            $messageHelper->sendMessage($message);
        }

        return $notify;
    }

    /**
     * Clear unread messages in a chat from a user
     * - Email (email)
     * - Password (password)
     * - Chat (chat id)
     *
     * @param Request $request
     */
    public function cleanUnreadMessages(Request $request) {
        // Get toUser
        /** @var LoginHelper $loginHelper */
        $loginHelper = $this->container->get('sopinet_login_helper');
        /** @var $user */
        try {
            $user = $loginHelper->getUser($request);
        } catch(Exception $e) {
            throw new Exception('User is required');
        }

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.default_entity_manager');

        /** @var ChatRepository $chatRepository */
        $chatRepository = $em->getRepository('SopinetChatBundle:Chat');

        /** @var Chat $chat */
        $chat = $chatRepository->findOneById($request->get('chat'));

        if(!$chat){
            throw new Exception(ChatHelper::NOT_EXIST);
        }

        $userInChat = $chatRepository->userInChat($user, $chat);

        if(!$userInChat){
            throw new Exception(ChatHelper::NOT_USER_EXIST);
        }

        /** @var Message $messageRepository */
        $messageRepository = $em->getRepository('SopinetChatBundle:Message');

        $messages = $messageRepository->findBy( array("chat" => $chat, "readed" => 0) );

        /** @var Message $message */
        foreach($messages as $message){
            $message->setReaded(1);
            $em->persist($message);
        }

        $em->flush();

        return $chat;
    }
}
