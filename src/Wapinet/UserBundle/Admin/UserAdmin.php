<?php
namespace Wapinet\UserBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use FOS\UserBundle\Model\UserManagerInterface;
use Wapinet\UserBundle\Entity\User;

class UserAdmin extends Admin
{
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'id',
    );

    protected $userManager;

    /**
     * Конфигурация отображения записи
     *
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'Идентификатор'))
            ->add('username', null, array('label' => 'Логин'))
            ->add('email', null, array('label' => 'Email'))
            ->add('enabled', null, array('label' => 'Активен'))
            ->add('locked', null, array('label' => 'Заблокирован'))
            ->add('last_login', 'datetime', array('label' => 'Последняя авторизация'))
            ->add('roles', null, array('label' => 'Роли'))
            ->add('created_at', 'datetime', array('label' => 'Регистрация'))
            ->add('updated_at', 'datetime', array('label' => 'Обновление профиля'))
            ->add('last_activity', 'datetime', array('label' => 'Последняя активность'))
            ->add('avatar', null, array('label' => 'Аватар'))
            ->add('sex', null, array('label' => 'Пол'))
            ->add('birthday', null, array('label' => 'День рождения'))
            ->add('timezone', null, array('label' => 'Временная зона'))
            ->add('info', null, array('label' => 'Дополнительная информация'))
            ->add('subscriber', null, array('label' => 'Подписки'))
            ->add('panel', null, array('label' => 'Меню'))
            ->add('friends', null, array('label' => 'Друзья'))
        ;
    }

    /**
     * Конфигурация формы редактирования записи
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $roles = array();
        foreach ($this->getConfigurationPool()->getContainer()->getParameter('security.role_hierarchy.roles') as $key => $value) {
            $roles[$key] = $key;
        }

        $formMapper
            ->add('enabled', null, array('label' => 'Активен', 'required' => false))
            ->add('locked', null, array('label' => 'Заблокирован', 'required' => false))
            ->add('plainPassword', 'text', array('label' => 'Пароль', 'required' => false))
            ->add('username', null, array('label' => 'Логин'))
            ->add('email', null, array('label' => 'Email'))
            ->add('roles', 'choice', array('choices' => $roles, 'multiple' => true))
            ->add('sex', 'choice', array('label' => 'Пол', 'required' => false, 'choices' => User::getSexChoices()))
            ->add('birthday', 'date', array('widget' => 'single_text', 'label' => 'День рождения', 'required' => false))
            ->add('timezone', 'timezone', array('label' => 'Временная зона', 'required' => false))
            ->add('info', 'textarea', array('label' => 'Дополнительная информация'))
            ->end()
            ->with('Аватар')
            ->add('avatar', 'file_url', array('delete_button' => true, 'label' => false, 'required' => false, 'accept' => 'image/*'))
            ->end()
            ->with('Подписки')
            ->add('subscriber', 'sonata_type_admin', array(
                'label' => false,
                'required' => true,
                'delete' => false,
                'btn_add' => false,
            ))
            ->end()
            ->with('Меню')
            ->add('panel', 'sonata_type_admin', array(
                'label' => false,
                'required' => true,
                'delete' => false,
                'btn_add' => false,
            ))
            ->end()
        ;
    }

    /**
     * Перед обновлением
     */
    public function preUpdate($user)
    {
        $this->getUserManager()->updateCanonicalFields($user);
        $this->getUserManager()->updatePassword($user);
    }

    /**
     * @param UserManagerInterface $userManager
     */
    public function setUserManager(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @return UserManagerInterface
     */
    public function getUserManager()
    {
        return $this->userManager;
    }


    /**
     * Поля, по которым производится поиск в списке записей
     *
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('username', null, array('label' => 'Логин'))
            ->add('email', null, array('label' => 'Email'))
            ->add('roles', null, array('label' => 'Роли'))
            ->add('lastActivity', 'doctrine_orm_datetime_range', array('label' => 'Последняя активность'), 'sonata_type_date_range', array('widget' => 'single_text'))
        ;
    }

    /**
     * Конфигурация списка записей
     *
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('username', null, array('label' => 'Логин'))
            ->add('email', null, array('label' => 'Email'))
            ->add('roles', null, array('label' => 'Роли'))
            ->add('created_at', 'datetime', array('label' => 'Регистрация'))
            ->add('last_activity', 'datetime', array('label' => 'Последняя активность'))
            ->add('_action', 'actions', array(
                'label' => 'Операции',
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }
}
