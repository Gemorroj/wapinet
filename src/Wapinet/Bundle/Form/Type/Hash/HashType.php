<?php
namespace Wapinet\Bundle\Form\Type\Hash;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Wapinet\Bundle\Helper\Hash;
use Wapinet\UploaderBundle\Form\Type\FileUrlType;

/**
 * Hash
 */
class HashType extends AbstractType
{
    private $hash;

    /**
     * HashType constructor.
     * @param Hash $hash
     */
    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $algorithms = $this->hash->getAlgorithms();
        $builder->add('algorithm', ChoiceType::class, array(
            'choices' => \array_flip($algorithms),
            'label' => 'Алгоритм',
            'preferred_choices' => array(
                array_search('md5', $algorithms, true),
                array_search('sha512', $algorithms, true),
                array_search('crc32', $algorithms, true)
            ),
        ));

        $builder->add('text', TextareaType::class, array('label' => 'Текст', 'required' => false));

        $builder->add('file', FileUrlType::class, array(
            'label' => false,
            'required' => false,
        ));

        $builder->add('submit', SubmitType::class, array('label' => 'Хэшировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'hash_form';
    }
}
