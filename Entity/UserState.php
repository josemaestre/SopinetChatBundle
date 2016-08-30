<?php
namespace Sopinet\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Entity UserState
 *
 * @ORM\Table("sopinet_chatbundle_userState")
 * @ORM\Entity(repositoryClass="Sopinet\ChatBundle\Entity\UserStateRepository")
 */
class UserState
{
    const STATE_CONNECTED = 1;
    const STATE_DISCONNECTED = 0;

    const GROUP_CREATE = "userState_group_create";

    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({UserState::GROUP_CREATE})
     */
    protected $id;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('0', '1')")
     */
    protected $state;

    /**
     * @ORM\OneToOne(targetEntity="\Sopinet\ChatBundle\Model\UserInterface", mappedBy="userState", cascade={"persist"})
     **/
    protected $user;

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

    /**
     * Set user
     *
     * @param $user
     * @return UserState
     */
    public function setUser($user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User 
     */
    public function getUser()
    {
        return $this->user;
    }
}
