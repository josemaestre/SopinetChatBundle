<?php
namespace Sopinet\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;
use Sopinet\ChatBundle\Model\MessageObject;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\DependencyInjection\Container;


/**
 * Entity MessagePackage
 *
 * @ORM\Entity
 * @DoctrineAssert\UniqueEntity("id")
 * @ORM\Table(name="sopinet_chatbundle_messagepackage")
 */
class MessagePackage
{
    const GROUP_CREATE = "messagePackage_group_create";
    const GROUP_EDIT = "messagePackage_group_edit";
    const GROUP_LOGIN = "messagePackage_group_login";

    const STATUS_OK = 1;
    const STATUS_KO = -1;
    const STATUS_PENDING = 0;

    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({MessagePackage::GROUP_CREATE, MessagePackage::GROUP_EDIT, MessagePackage::GROUP_LOGIN})
     */
    protected $id;

    // TODO: ESTO NO PUEDE ESTAR AQUí; MOVERLO A CHAT
    /**
     * @ORM\ManyToOne(targetEntity="\Sopinet\ChatBundle\Entity\Message", inversedBy="messagesGenerated", cascade={"persist"})
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Exclude
     */
    protected $message;

    /**
     * @ORM\ManyToOne(targetEntity="\Sopinet\ChatBundle\Model\UserInterface", inversedBy="messagesPackageReceived", cascade={"persist"})
     * @ORM\JoinColumn(name="toUser_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @ORM\OrderBy({"id" = "DESC"})
     * @Exclude
     */
    protected $toUser;

    /**
     * @ORM\ManyToOne(targetEntity="Device", inversedBy="messagesPackageReceived", cascade={"persist"})
     * @ORM\JoinColumn(name="toDevice_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Exclude
     */
    protected $toDevice;

    /**
     * @ORM\Column(type="integer")
     */
    protected $status;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $processed;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        //$this->processed = 0;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return MessagePackage
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get integer
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set processed
     *
     * @param integer $processed
     * @return MessagePackage
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;

        return $this;
    }

    /**
     * Get integer
     *
     * @return integer
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * Set message
     *
     * @param \Sopinet\ChatBundle\Entity\Message $message
     * @return MessagePackage
     */
    public function setMessage(\Sopinet\ChatBundle\Entity\Message $message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return \Sopinet\ChatBundle\Entity\Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set toUser
     *
     * @param User $toUser
     * @return MessagePackage
     */
    public function setToUser($toUser = null)
    {
        $this->toUser = $toUser;

        return $this;
    }

    /**
     * Get toUser
     *
     * @return User
     */
    public function getToUser()
    {
        return $this->toUser;
    }

    /**
     * Set toDevice
     *
     * @param \Sopinet\ChatBundle\Entity\Device $toDevice
     * @return MessagePackage
     */
    public function setToDevice(\Sopinet\ChatBundle\Entity\Device $toDevice = null)
    {
        $this->toDevice = $toDevice;

        return $this;
    }

    /**
     * Get toDevice
     *
     * @return \Sopinet\ChatBundle\Entity\Device
     */
    public function getToDevice()
    {
        return $this->toDevice;
    }

    public function __toString() {
        if ($this->getToUser() == null) return (string) $this->id;
        return $this->getToUser()->__toString() . " - " . $this->getToDevice()->__toString();
    }
}