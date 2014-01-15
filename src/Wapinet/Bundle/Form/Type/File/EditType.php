<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Edit
 */
class EditType extends AbstractType
{
    /**
     * @var string
     */
    protected $tagsString;

    /**
     * @param string $tagsString
     */
    public function __construct($tagsString)
    {
        $this->tagsString = $tagsString;
    }

    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', 'file_url', array('required' => false, 'label' => false));
        $builder->add('description', 'textarea', array('max_length' => 5000, 'required' => true, 'label' => 'Описание', 'constraints' => new NotBlank()));

        // http://view.jquerymobile.com/1.3.2/dist/demos/widgets/autocomplete/autocomplete-remote.html
        // тэги
        $builder->add('tags_string', 'text', array('data' => $this->tagsString, 'required' => false, 'label' => 'Тэги через запятую', 'mapped' => false));


        $builder->add('password', 'password', array('required' => false, 'label' => 'Пароль', 'attr' => array('autocomplete' => 'off')));

        $builder->add('submit', 'submit', array('label' => 'Загрузить'));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Wapinet\Bundle\Entity\File',
        ));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'file_edit_form';
    }
}
