<?php

namespace Sopinet\ChatBundle\Admin;

use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sopinet\ChatBundle\Entity\MessageRepository;

class MessageAdmin extends Admin
{
    /**
     * Default Datagrid values
     *
     * @var array
     */
    protected $datagridValues = array(
        '_sort_by' => 'createdAt',
        '_sort_order' => 'DESC', // Descendant ordering (default = 'ASC')
    );

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $container = $this->getConfigurationPool()->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        /** @var MessageRepository $reMessages */
        $reMessages = $em->getRepository("SopinetChatBundle:Message");
        $types = $reMessages->getTypesMessage();

        //ldd($types);

        $datagridMapper
            ->add('id')
            ->add('chat')
            ->add('fromDevice')
            ->add('fromDevice.deviceType', 'doctrine_orm_choice', [], 'choice', array('choices' => array(
                'iOS' => 'iOS',
                'Android' => 'Android'
            )))
            ->add('fromUser')
            ->add('createdAt', 'doctrine_orm_date', array(
                    'field_type' => 'sonata_type_date_picker',
                    'format' => 'd/m/Y'
                )
            )
            ->add('fromTime', 'doctrine_orm_date', array(
                    'field_type' => 'sonata_type_date_picker',
                    'format' => 'd/m/Y'
                )
            )
            ->add('text')
            ->add('typeClient', 'doctrine_orm_choice', [], 'choice', array('choices' => $types))
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('chat')
            ->add('typeClient')
            ->add('fromDevice')
            ->add('text')
            ->add('createdAt')
            ->add('fromTime')
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('id')
            ->add('chat')
            ->add('fromDevice')
            ->add('text')
            //->add('messagesGenerated')
            ->add('createdAt')
            ->add('fromTime')
            ->add('fromUser')
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('chat')
            ->add('fromDevice')
            ->add('text')
            //->add('messagesGenerated')
            ->add('createdAt')
            ->add('fromTime')
            ->add('fromUser')
            ->add('mytype')
            ->add('anytype')
            ->add('typeClient')
        ;
    }
}