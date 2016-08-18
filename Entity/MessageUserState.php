<?php
namespace Sopinet\ChatBundle\Entity;
use Sopinet\ChatBundle\Entity\Message;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class MessageUserState extends Message
{
    public function __construct() {
        parent::__construct();
        $this->setId(uniqid("SERVER_"));
    }

    public function getMyType() {
        return "userState";
    }

    public function getMyDestionationUsers($container)
    {
        // Obtenemos los usuarios a los que enviaremos
        // Todos los usuarios de los Chats donde está el que envía el mensaje, sin repetir y obviando el que envía el mensaje
        $toUsers = array();

        /** @var Chat $chat */
        foreach($this->getFromUser()->getChats() as $chat) {
            /** @var User $chatMember */
            foreach($chat->getMyDestinationUsers($container) as $chatMember) {
                if ($chatMember->getId() != $this->getFromUser()->getId()) {
                    $is = false;
                    /** @var $user */
                    foreach($toUsers as $user) {
                        if ($user->getId() == $chatMember->getId()) $is = true;
                    }
                    if (!$is) {
                        $toUsers[] = $chatMember;
                    }
                }
            }
        }

        return $toUsers;
    }

    public function getMyIOSContentAvailable() {
        return false;
    }
}