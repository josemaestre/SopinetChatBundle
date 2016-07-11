<?php
namespace Sopinet\ChatBundle\Entity;

use Sopinet\ChatBundle\Model\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use JMS\Serializer\Annotation\Groups;

use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * @ORM\Entity(repositoryClass="Sopinet\ChatBundle\Entity\DeviceRepository")
 * @ORM\Table(name="sopinet_chatbundle_device")
 * @DoctrineAssert\UniqueEntity("deviceId")
 */
class Device
{
    use ORMBehaviors\Timestampable\Timestampable;

    const TYPE_IOS = "iOS";
    const TYPE_ANDROID = "Android";
    const TYPE_WEB = "Web";

    /**
     * VirtualNone type is used for custom functionality about Devices and Messages.
     * By example, you can to use it for check messages as notifications manually
     */
    const TYPE_VIRTUALNONE = "VirtualNone";

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     * @Groups({"create"})
     *
     * DeviceToken from Device
     *
     */
    protected $deviceId;

    /**
     * @var string
     *
     * @ORM\Column(name="deviceGCMId", type="string", nullable=true)
     * @Groups({"create"})
     *
     */
    protected $deviceGCMId;

    /**
     * @ORM\ManyToMany(targetEntity="\Sopinet\ChatBundle\Model\UserInterface", inversedBy="devices")
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="fromDevice", cascade={"remove"})
     */
    protected $messages;

    /**
     * @ORM\OneToMany(targetEntity="MessagePackage", mappedBy="toDevice", cascade={"remove"})
     */
    protected $messagesPackageReceived;

    /**
     * @var string
     * iOS
     * Android
     * Web
     * @ORM\Column(name="type", type="string", columnDefinition="enum('iOS','Android','Web','VirtualNone')")
     */
    protected $deviceType;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('0', '1')")
     */
    protected $state;

    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    /**
     * Get deviceId
     *
     * @return string
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function setDeviceGCMId($deviceGCMId)
    {
        $this->deviceGCMId = $deviceGCMId;
    }

    public function getDeviceGCMId()
    {
        return $this->deviceGCMId;
    }

    /**
     * Set deviceType
     *
     * @param string $deviceType
     *
     * @return Device
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    /**
     * Get deviceType
     *
     * @return string
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    public function __toString() {
        return $this->getDeviceId();
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setState('1');
    }

    /**
     * Add user
     *
     * @param $user
     * @return Device
     */
    public function addUser($user)
    {
        $this->user[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param $user
     */
    public function removeUser($user)
    {
        $this->user->removeElement($user);
    }

    /**
     * Get user
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add message
     *
     * @param Message $message
     *
     * @return Device
     */
    public function addMessage(Message $message)
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Remove message
     *
     * @param Message $message
     */
    public function removeMessage(MessagePackage $message)
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
     * Add message
     *
     * @param MessagePackage $messagePackageReceived
     *
     * @return Device
     */
    public function addMessagePackageReceived(MessagePackage $messagePackageReceived)
    {
        $this->messagesPackageReceived[] = $messagePackageReceived;

        return $this;
    }

    /**
     * Remove message
     *
     * @param MessagePackage $messagePackageReceived
     */
    public function removeMessagePackageReceived(MessagePackage $messagePackageReceived)
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

    /**
     * Add messagesPackageReceived
     *
     * @param \Sopinet\ChatBundle\Entity\MessagePackage $messagesPackageReceived
     * @return Device
     */
    public function addMessagesPackageReceived(\Sopinet\ChatBundle\Entity\MessagePackage $messagesPackageReceived)
    {
        $this->messagesPackageReceived[] = $messagesPackageReceived;

        return $this;
    }

    /**
     * Remove messagesPackageReceived
     *
     * @param \Sopinet\ChatBundle\Entity\MessagePackage $messagesPackageReceived
     */
    public function removeMessagesPackageReceived(\Sopinet\ChatBundle\Entity\MessagePackage $messagesPackageReceived)
    {
        $this->messagesPackageReceived->removeElement($messagesPackageReceived);
    }

    /**
     * Set state
     *
     * @param string $state
     * @return UserState
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
