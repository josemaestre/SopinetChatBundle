<?php

namespace Sopinet\ChatBundle\Model;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
/**
 * Message trait.
 *
 * Should be used inside entity, that needs to be one User for chats.
 */
trait UserGCM
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->messagesPackageReceived = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @ORM\OneToMany(targetEntity="\Sopinet\ChatBundle\Entity\MessagePackage", mappedBy="toUser", cascade={"remove"})
     */
    protected $messagesPackageReceived;

    /**
     * Add message
     *
     * @param \Sopinet\ChatBundle\Entity\MessagePackage $messagePackageReceived
     *
     * @return User
     */
    public function addMessagePackageReceived(\Sopinet\ChatBundle\Entity\MessagePackage $messagePackageReceived)
    {
        $this->messagesPackageReceived[] = $messagePackageReceived;

        return $this;
    }

    /**
     * Remove message
     *
     * @param \Sopinet\ChatBundle\Entity\MessagePackage $messagePackageReceived
     */
    public function removeMessagePackageReceived(\Sopinet\ChatBundle\Entity\MessagePackage $messagePackageReceived)
    {
        $this->messagesPackageReceived->removeElement($messagePackageReceived);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMessagesPackageReceived()
    {
        return $this->messagesPackageReceived;
    }
}