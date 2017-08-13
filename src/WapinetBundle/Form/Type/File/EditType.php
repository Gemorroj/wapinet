<?php
namespace WapinetBundle\Form\Type\File;

use Symfony\Component\Form\Extension\Core\Type\PasswordType as CorePasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WapinetBundle\Entity\File;
use WapinetBundle\Form\Type\TagsType;
use WapinetUploaderBundle\Form\Type\FileUrlType;

/**
 * Edit
 */
class EditType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, array('required' => false, 'label' => false));
        $builder->add('description', TextareaType::class, array('required' => true, 'label' => 'Описание'));

        // http://view.jquerymobile.com/1.3.2/dist/demos/widgets/autocomplete/autocomplete-remote.html
        // тэги
        $builder->add('tags', TagsType::class, array('required' => false, 'label' => 'Тэги через запятую'));


        $builder->add('plainPassword', CorePasswordType::class, array('required' => false, 'label' => 'Пароль', 'attr' => array('autocomplete' => 'off')));

        $builder->add('submit', SubmitType::class, array('label' => 'Загрузить'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => File::class,
        ));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'file_edit_form';
    }
}
