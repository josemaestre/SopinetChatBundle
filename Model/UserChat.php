<?php

namespace Sopinet\ChatBundle\Model;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Sopinet\ChatBundle\SopinetChatBundle;


/**
 * Message trait.
 *
 * Should be used inside entity, that needs to be one User for chats.
 */
trait UserChat
{
    use \Sopinet\ChatBundle\Model\UserGCM {
        __construct as _trait_gcm_constructor;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_trait_gcm_constructor();
        $this->messages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->chats = new \Doctrine\Common\Collections\ArrayCollection();
        $this->chatsOwned = new \Doctrine\Common\Collections\ArrayCollection();
        $this->devices = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @ORM\ManyToMany(targetEntity="\Sopinet\ChatBundle\Entity\Device", mappedBy="user")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $devices;


    /**
     * @ORM\OneToMany(targetEntity="\Sopinet\ChatBundle\Entity\Message", mappedBy="fromUser", cascade={"persist"})
     */
    protected $messages;

    /**
     * @ORM\ManyToMany(targetEntity="\Sopinet\ChatBundle\Entity\Chat", inversedBy="chatMembers")
     * @ORM\JoinColumn(name="chat_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $chats;

    /**
     * @ORM\OneToMany(targetEntity="\Sopinet\ChatBundle\Entity\Chat", mappedBy="admin", cascade={"persist"})
     */
    protected $chatsOwned;

    /**
     * @ORM\OneToOne(targetEntity="\Sopinet\ChatBundle\Entity\UserState", inversedBy="user", cascade={"persist"})
     **/
    private $userState;

    /**
     * Add message
     *
     * @param \Sopinet\ChatBundle\Entity\Message $message
     *
     * @return User
     */
    public function addMessage(\Sopinet\ChatBundle\Entity\Message $message)
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Remove message
     *
     * @param \Sopinet\ChatBundle\Entity\Message $message
     */
    public function removeMessage(\Sopinet\ChatBundle\Entity\Message $message)
    {
        $this->messages->removeElement($message);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Add device
     *
     * @param \Sopinet\ChatBundle\Entity\Device $device
     *
     * @return User
     */
    public function addDevice(\Sopinet\ChatBundle\Entity\Device $device)
    {
        $this->devices[] = $device;

        return $this;
    }

    /**
     * Remove message
     *
     * @param \Sopinet\ChatBundle\Entity\Device $device
     */
    public function removeDevice(\Sopinet\ChatBundle\Entity\Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * Add chat
     *
     * @param \Sopinet\ChatBundle\Entity\Chat $chat
     *
     * @return User
     */
    public function addChat(\Sopinet\ChatBundle\Entity\Chat $chat)
    {
        $this->chats[] = $chat;

        return $this;
    }

    /**
     * Remove chat
     *
     * @param \Sopinet\ChatBundle\Entity\Chat $chat
     */
    public function removeChat(\Sopinet\ChatBundle\Entity\Chat $chat)
    {
        if (!$this->chats->contains($chat)) {
            return;
        }
        $this->chats->removeElement($chat);
        $chat->removeChatMember($this);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChats()
    {
        return $this->chats;
    }

    /**
     * Set userState
     *
     * @param \Sopinet\ChatBundle\Entity\UserState $userState
     *
     * @return User
     */
    public function setUserState(\Sopinet\ChatBundle\Entity\UserState $userState = null)
    {
        $this->userState = $userState;

        return $this;
    }

    /**
     * Get userState
     *
     * @return \Sopinet\ChatBundle\Entity\UserState
     */
    public function getUserState()
    {
        return $this->userState;
    }
}