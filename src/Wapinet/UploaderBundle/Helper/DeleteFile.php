<?php
namespace Wapinet\UploaderBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

/**
 * Delete File
 */
class DeleteFile
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var PropertyMappingFactory
     */
    protected $factory;


    public function __construct(Request $request, PropertyMappingFactory $factory)
    {
        $this->request = $request;
        $this->factory = $factory;
    }


    /**
     * Удаляет из объекта файл, если указано пользователем
     *
     * @param FormInterface $form
     * @param object $entity
     */
    public function delete(FormInterface $form, $entity)
    {
        $data = $this->request->get($form->getName());

        /** @var PropertyMapping[] $mappings */
        $mappings = $this->factory->fromObject($entity);
        foreach ($mappings as $mapping) {
            $formName = $mapping->getPropertyName();
            $fileName = $mapping->getFileNamePropertyName();

            $fileForm = $form->get($formName);
            $fileFormName = $fileForm->getName();
            if (isset($data[$fileFormName]['file_url_delete']) && $data[$fileFormName]['file_url_delete'] && null === $fileForm->getData()) {
                $entity->{'set' . ucfirst($formName)}(null);
                $entity->{'set' . ucfirst($fileName)}(null);
            }
        }
    }
}
