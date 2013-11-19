<?php

namespace Wapinet\FileStoreBundle\Form\Type;

use Iphp\FileStoreBundle\Form\Type\FileType as BaseType;
use Iphp\FileStoreBundle\Form\Type\FileTypeBindSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Iphp\FileStoreBundle\Form\DataTransformer\FileDataTransformer;

/**
 * @author Vitiko <vitiko@mail.ru>
 */
class FileType extends BaseType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new FileDataTransformer($this->fileStorage);
        $subscriber = new FileTypeBindSubscriber($this->mappingFactory,$this->dataStorage,  $transformer);
        $builder->addEventSubscriber($subscriber);


        $builder->add('file', 'file_url', array('attr' => array('accept' => 'image/*'), 'required' => false))
            ->add('delete', 'checkbox', array('required' => false))
            ->addViewTransformer($transformer);

        //for sonata admin
        //    ->addViewTransformer(new FileDataViewTransformer());
    }
}



