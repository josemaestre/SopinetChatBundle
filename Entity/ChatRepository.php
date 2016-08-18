<?php
namespace Sopinet\ChatBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ChatRepository extends EntityRepository
{
    /**
     * Función que comprueba si existe un chat y lo devuelve
     * para una serie de Usuarios pasados por parámetro
     * Si no existe devuelve null
     *
     * TODO: Hará uso de getChatMembers, no de la función getMyDestinationUsers
     *
     * @param $users
     *
     * @return Chat
     */
    public function getChatExist($users)
    {
        $em = $this->getEntityManager();
        $repositoryChat = $em->getRepository('SopinetChatBundle:Chat');
        $chats=$repositoryChat->findAll();
        /** @var Chat $chat */
        foreach ($chats as $chat) {
            if ($this->usersInChat($users, $chat)) {
                return $chat;
            }
        }

        return null;
    }

    /**
     * Comprueba si un conjunto de Usuarios pertenecen a un Chat
     * Devuelve true si todos están en Chat
     * false en caso contrario
     *
     * @param $users
     * @param Chat $chat
     * @return bool
     */
    private function usersInChat($users, Chat $chat) {
        if (count($users) == 0) return false;
        foreach($users as $user) {
            if (!$this->userInChat($user, $chat)) return false;
        }
        return true;
    }

    /**
     * Comprueba si un usuario esta dentro de un chat
     * @param $user
     * @param Chat $chat
     * @return bool
     */
    public function userInChat($user,Chat $chat)
    {
        return in_array($user, $chat->getChatMembers()->toArray());
    }


    /**
     * Function to enabled chat
     * @param Chat $chat
     */
    public function enabledChat(Chat $chat){

        $em = $this->getEntityManager();

        $chat->setEnabled(true);

        $em->persist($chat);
        $em->flush();
    }
    
     /**
     *
     * @param Chat $chat
     * @param $user
     * @return Message
     */
    public function getFirstMessage(Chat $chat, $user){

        $em = $this->getEntityManager();
        $repositoryMessage = $em->getRepository('SopinetChatBundle:Message');

        $message = $repositoryMessage->findOneBy(array('typeClient'=> 'text', 'chat'=> $chat, 'fromUser' => $user), array('createdAt' => 'ASC'));

        return $message;
    }
}
