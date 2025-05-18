<?php

namespace App\Form\Type\User;

use App\Entity\UserPanel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PanelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('forum', CheckboxType::class, ['required' => false, 'label' => 'Форум']);
        $builder->add('guestbook', CheckboxType::class, ['required' => false, 'label' => 'Гостевая']);
        $builder->add('gist', CheckboxType::class, ['required' => false, 'label' => 'Блоги']);
        $builder->add('file', CheckboxType::class, ['required' => false, 'label' => 'Файлообменник']);
        $builder->add('archiver', CheckboxType::class, ['required' => false, 'label' => 'Архиватор']);
        $builder->add('http', CheckboxType::class, ['required' => false, 'label' => 'HTTP клиент']);
        $builder->add('whois', CheckboxType::class, ['required' => false, 'label' => 'WHOIS/RDAP']);
        $builder->add('phpValidator', CheckboxType::class, ['required' => false, 'label' => 'PHP валидатор']);
        $builder->add('htmlValidator', CheckboxType::class, ['required' => false, 'label' => 'HTML валидатор']);
        $builder->add('cssValidator', CheckboxType::class, ['required' => false, 'label' => 'CSS валидатор']);
        $builder->add('phpObfuscator', CheckboxType::class, ['required' => false, 'label' => 'PHP обфускатор']);
        $builder->add('audioTags', CheckboxType::class, ['required' => false, 'label' => 'Редактор аудио тегов']);
        $builder->add('rename', CheckboxType::class, ['required' => false, 'label' => 'Переименование файлов']);
        $builder->add('email', CheckboxType::class, ['required' => false, 'label' => 'Отправка E-mail']);
        $builder->add('browserInfo', CheckboxType::class, ['required' => false, 'label' => 'Информация о браузере']);
        $builder->add('hash', CheckboxType::class, ['required' => false, 'label' => 'Хэширование данных']);
        $builder->add('code', CheckboxType::class, ['required' => false, 'label' => 'Конвертирование данных']);
        $builder->add('unicode', CheckboxType::class, ['required' => false, 'label' => 'Конвертер в Unicode']);
        $builder->add('unicodeIcons', CheckboxType::class, ['required' => false, 'label' => 'Пиктограммы в Unicode']);
        $builder->add('politics', CheckboxType::class, ['required' => false, 'label' => 'Политика']);
        $builder->add('rates', CheckboxType::class, ['required' => false, 'label' => 'Курсы валют']);
        $builder->add('mobileCode', CheckboxType::class, ['required' => false, 'label' => 'Телефонные коды']);
        $builder->add('openSource', CheckboxType::class, ['required' => false, 'label' => 'Open source разработки']);
        $builder->add('textbook', CheckboxType::class, ['required' => false, 'label' => 'Учебники']);
        $builder->add('videoCourses', CheckboxType::class, ['required' => false, 'label' => 'Видео-курсы']);

        $builder->add('submit', SubmitType::class, ['label' => 'Изменить']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPanel::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wapinet_user_panel';
    }
}
