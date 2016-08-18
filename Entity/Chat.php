<?php
namespace Sopinet\ChatBundle\Entity;

use Sopinet\ChatBundle\Model\UserInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Entity\UserManager;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Entity Chat
 *
 * @ORM\Table("sopinet_chatbundle_chat")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @ORM\Entity(repositoryClass="Sopinet\ChatBundle\Entity\ChatRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"chat" = "Chat"})
 * @Vich\Uploadable
 */
class Chat
{
    const CHAT_CREATE = "chat_create";
    const CHAT_LIST = "chat_list";

    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @var \DateTime $deletedAt
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({Chat::CHAT_CREATE, Chat::CHAT_LIST})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     * @Groups({Chat::CHAT_CREATE, Chat::CHAT_LIST})
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="\Sopinet\ChatBundle\Model\UserInterface", mappedBy="chats")
     * @Groups({Chat::CHAT_CREATE, Chat::CHAT_LIST})
     */
    protected $chatMembers;

    /**
     * Administrador o persona que inicia el Chat
     *
     * @ORM\ManyToOne(targetEntity="\Sopinet\ChatBundle\Model\UserInterface", inversedBy="chatsOwned", cascade={"persist"})
     * @ORM\JoinColumn(name="admin_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Groups({Chat::CHAT_CREATE, Chat::CHAT_LIST})
     */
    protected $admin;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="chat", cascade={"persist"})
     * @ORM\OrderBy({"createdAt" = "ASC"})
     * @Exclude
     */
    protected $messages;

    /**
     * @ORM\Column(name="enabled", type="boolean", nullable=true, options={"default" = 1})
     * @Groups({Chat::CHAT_CREATE, Chat::CHAT_LIST})
     */
    protected $enabled;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="group_photo", fileNameProperty="imageName")
     *
     * @var File
     */
    protected $groupPhoto;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    protected $imageName;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name = "";
        $this->enabled = false;
        $this->chatMembers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->messages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Chat
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Add message
     *
     * @param Message $message
     *
     * @return Chat
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
    public function removeMessage(Message $message)
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

    public function __toString() {
        return $this->getName();
    }

    /**
     * Devuelve el último mensaje del Chat
     */
    public function refreshLastMessage() {
        $this->last_message = $this->getMessages()[0];
        return $this->last_message;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     * @return Chat
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

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
     * Add chatMembers
     *
     * @param $chatMembers
     * @return Chat
     */
    public function addChatMember($chatMembers)
    {
        $this->chatMembers[] = $chatMembers;
        $chatMembers->addChat($this);

        return $this;
    }

    /**
     * Remove chatMember
     *
     * @param $chatMember
     */
    public function removeChatMember($chatMember)
    {
        if (!$this->chatMembers->contains($chatMember)) {
            return;
        }
        $this->chatMembers->removeElement($chatMember);
        $chatMember->removeChat($this);
    }

    /**
     * Get chatMembers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChatMembers()
    {
        return $this->chatMembers;
    }

    /**
     * Return array of users for send Message
     * This function may be override by another chat extends.
     * So, you can send messages with dynamic logic.
     *
     * @param $container
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMyDestinationUsers($container) {
        return $this->getChatMembers();
    }

    /**
     * Set admin
     *
     * @param $admin
     * @return Chat
     */
    public function setAdmin($admin = null)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return User
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Devuelve los dispositivos de todos los usuarios
     * vinculados al Chat
     *
     * @return array|bool
     */
    public function getDevices()
    {
        $devices = array();
        foreach ($this->getChatMembers() as $chatMember) {
            /* @var $chatMember User */
            // Devices to Array
            $devicesObject = $chatMember->getDevices();
            foreach ($devicesObject as $do) {
                $devices[] = $do;
            }
        }

        return $devices;
    }

    /**
     * Return type data
     *
     * @return string
     */
    public function getMyType() {
        $className = get_class($this);
        $classParts = explode("\\", $className);
        $classSingle = $classParts[count($classParts) - 1];
        $classLowSingle = strtolower($classSingle);
        $type = str_replace("chat", "", $classLowSingle);

        if (!$type) {
            return "chat";
        } else {
            return $type;
        }
    }

    /**
     * FormClass for save and edit data from Chat entity
     * Customizable
     *
     * @return string
     */
    public function getMyForm() {
        return "\Sopinet\ChatBundle\Form\ChatType";
    }

    /**
     * Return null or Chat
     * If chat exists (parameters from Request, searching parameters customizable)
     *
     * @param $container
     * @param Request $request
     * @return null|Chat
     */
    public function getMyChatExist($container, Request $request) {
        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();

        /** @var UserManager $userManager */
        $userManager = $container->get('fos_user.user_manager');

        $chatMembersString = $request->get('chatMembers');
        $chatMembersArray = explode(',', $chatMembersString);
        $users = array();
        foreach($chatMembersArray as $chatMemberID) {
            $chatMember = $userManager->findUserBy(array('id' => $chatMemberID));
            if ($chatMember == null) return null;
            $users[] = $chatMember;
        }

        /** @var ChatRepository $reChat */
        $reChat = $em->getRepository('SopinetChatBundle:Chat');

        return $reChat->getChatExist($users);
    }

    /**
     * Add message information to MessageObject (for send) (customizable)
     * @return \stdClass
     */
    public function getMyAddMessageObject($container){
        $add = new \stdClass();
        return $add;
    }

    /** Set enabled
     *
     * @param $enabled
     * @return Chat
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Chat
     */
    public function setGroupPhoto(File $image = null)
    {
        $this->groupPhoto = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime('now');
        }

        return $this;
    }

    /**
     * @return File
     */
    public function getGroupPhoto()
    {
        return $this->groupPhoto;
    }

    /**
     * @param string $imageName
     *
     * @return Chat
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @return string
     * @Groups({Chat::CHAT_CREATE, Chat::CHAT_LIST})
     * @SerializedName("photo")
     * @VirtualProperty()
     */
    public function getPhotoPath()
    {
        if(!$this->imageName)
            return null;
        else
            return 'images/chat/' . $this->imageName;
    }
}
