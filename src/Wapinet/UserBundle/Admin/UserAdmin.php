<?php
namespace Wapinet\UserBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Validator\ErrorElement;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use FOS\UserBundle\Model\UserManagerInterface;
use Wapinet\UserBundle\Entity\User;

class UserAdmin extends Admin
{
    protected $userManager;
    /**
     * Конфигурация отображения записи
     *
     * @param ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'Идентификатор'))
            ->add('username', null, array('label' => 'Логин'))
            ->add('email', null, array('label' => 'Email'))
            ->add('enabled', null, array('label' => 'Активен'))
            ->add('locked', null, array('label' => 'Заблокирован'))
            ->add('last_login', 'datetime', array('label' => 'Последняя авторизация'))
            ->add('roles', null, array('label' => 'Роли'))
            ->add('created_at', 'datetime', array('label' => 'Зарегистрирован'))
            ->add('updated_at', 'datetime', array('label' => 'Обновление профиля'))
            ->add('avatar', null, array('label' => 'Аватар'))
            ->add('sex', null, array('label' => 'Пол'))
            ->add('birthday', null, array('label' => 'День рождения'))
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
            ->add('avatar', 'iphp_file', array('label' => 'Аватар', 'required' => false))
            ->add('roles', 'choice', array('choices' => $roles, 'multiple' => true))
            ->add('sex', 'choice', array('label' => 'Пол', 'choices' => array(User::SEX_M => 'Мужской', User::SEX_F => 'Женский')))
            ->add('birthday', 'date', array('widget' => 'single_text', 'label' => 'День рождения', 'required' => false))
        ;
    }

    /**
     * {@inheritdoc}
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
            ->add('id')
            ->addIdentifier('username', null, array('label' => 'Логин'))
            ->add('email', null, array('label' => 'Email'))
            ->add('roles', null, array('label' => 'Роли'))
            ->add('created_at', 'datetime', array('label' => 'Зарегистрирован'))
            ->add('last_login', 'datetime', array('label' => 'Последняя авторизация'))
        ;
    }
}
