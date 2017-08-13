<?php
namespace WapinetBundle\Helper;

use Symfony\Component\Form\FormInterface;

/**
 * Form errors хэлпер
 */
class Error
{
    /**
     * Получаем все ошибки в форме и ее потомках
     *
     * @param FormInterface $form
     * @return array
     */
    public function makeErrors (FormInterface $form)
    {
        $errors = array();

        if (!$form->isValid() && $form->getErrors()) {
            foreach ($form->getErrors() as $er) {
                $errors[] = $er->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            $errors = \array_merge($errors, $this->makeErrors($child));
        }

        return $errors;
    }
}
