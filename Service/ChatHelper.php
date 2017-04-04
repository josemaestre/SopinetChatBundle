<?php

namespace Sopinet\ChatBundle\Service;

use AppBundle\Services\LoginHelper;
use Sopinet\ChatBundle\Entity\Chat;
use Sopinet\ChatBundle\Entity\ChatRepository;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Class ChatHelper
 * @package Sopinet\ChatBundle\Service
 */
class ChatHelper
{

    const NOT_EXIST = "Chat no exist";
    const NOT_USER_EXIST = "User no exist in this chat";

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get Chat class String from $type
     *
     * @param $type
     * @return null|string
     */
    public function getChatClassString($type) {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        $chatClass = "Sopinet\ChatBundle\Entity\Chat";

        $cmf = $em->getMetadataFactory();
        $meta = $cmf->getMetadataFor($chatClass);

        $config = $this->container->getParameter('sopinet_chat.config');
        foreach($meta->discriminatorMap as $typeString => $typeClass) {
            if ($typeString == $type) {
                return $typeClass;
            }
        }

        // Type unknow
        return null;
    }

    /**
     * Get Chat class Object from $type
     *
     * @param $type
     * @return mixed
     * @throw If not found type
     */
    public function getChatClassObject($type = null) {
        // Type is optional
        if ($type == null) return new Chat();

        $chatClassString = $this->getChatClassString($type);
        if ($chatClassString == null) {
            throw new Exception("Error Type");
        }

        $chatClassObject = new $chatClassString;

        return $chatClassObject;
    }

    /**
     * Obtiene un Chat si existe
     * Se le pasan los usuarios en Request->get('chatMembers')
     * También se puede pasar el id del chat en Request->get('chat')
     * También se puede pasar como parámetro, en chatID
     *
     * Se le pasa el usuario en Request y se hace un Login
     * TODO: Se debería poder configurar para trabajar con Chat que no haya que recuperar
     *
     * @param $userStringArray
     */
    public function getChatExist($request, $chatID = null) {
        // TODO: Comprobar que el usuario logueado es uno de los que pregunta por el CHAT
        $loginHelper = $this->container->get('sopinet_login_helper');
        try {
            $user = $loginHelper->getUser($request);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        $em = $this->container->get('doctrine.orm.default_entity_manager');

        $reChat = $em->getRepository('SopinetChatBundle:Chat');
        if ($chatID != null) {
            $chat = $reChat->find($chatID);
            if ($chat != null) return $chat;
        }

        if ($request->get('chat') != "") {
            $chat = $reChat->find($request->get('chat'));
            if ($chat != null) return $chat;
        }

        /** @var Chat $chatClassObject */
        try {
            $chatClassObject = $this->getChatClassObject($request->get('type'));
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        try {
            $chatExist = $chatClassObject->getMyChatExist($this->container, $request);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $chatExist;
    }

    /**
     * Funcion para obtener mis chats
     * @param Request $request
     * @return array
     */
    public function getMyChats(Request $request){
        
        /** @var LoginHelper $loginHelper */
        $loginHelper = $this->container->get('sopinet_login_helper');
        try {
            $user = $loginHelper->getUser($request);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        $em = $this->container->get('doctrine.orm.default_entity_manager');

        /** @var ChatRepository $reChat */
        $reChat = $em->getRepository('SopinetChatBundle:Chat');

        $all = $reChat->findAll();

        $chats = array();

        /** @var Chat $chat */
        foreach ($all as $chat){

            if($chat->getChatMembers()->contains($user)){
                $chats [] = $chat;
            }
        }

        return $chats;
    }
}
