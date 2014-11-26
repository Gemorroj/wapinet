<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\Form\Form;

/**
 * BotChecker хэлпер
 */
class BotChecker
{
    /**
     * @param Form $form
     * @throws \Exception
     */
    public function checkForm(Form $form)
    {
        $data = $form->getData();

        if (isset($data['bot-checker'])) {
            throw new \Exception('Кажется, вы - спам-бот.');
        }
    }
}
