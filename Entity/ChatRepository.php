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
     * @param $users
     *
     * @return Chat
     */
    public function getChatExist($users)
    {
        $em = $this->getEntityManager();
        $repositoryChat = $em->getRepository('SopinetChatBundle:Chat');
        $qb=$repositoryChat->createQueryBuilder('chat');
        $chats=$qb
            ->join('chat.chatMembers',
                'user',
                'WITH',
                $qb->expr()->in('user.id', array_map(function($user){return $user->getId();},$users)))
            ->getQuery()->execute();
        /** @var Chat $chat */
        foreach ($chats as $chat) {
            if ($this->usersInChat($users, $chat))
            {
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
        $chatMembers=$chat->getChatMembers();
        $chatMembers->initialize();
        return $chatMembers->count() == count($users)
            && $chatMembers->forAll(function($index, $user) use ($users){
                return in_array($user, $users);
            });
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
