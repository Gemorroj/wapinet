<?php
namespace Wapinet\Bundle\Form\Type\Sms;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Sms
 */
class SmsType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('message', 'textarea', array('max_length' => 160, 'label' => 'Сообщение'));
        $builder->add('number', 'text', array('pattern' => '^\+?[0-9]+$', 'label' => 'Номер'));

        /**
         * @see http://www.en2ru.com/mobile.php
         */
        $builder->add('gateway', 'choice', array(
            'empty_value' => 'Выберите оператора',
            'label' => 'Оператор',
            'choices' => array(
                'Азербайджан' => array(
                    'sms.azercell.com' => 'Azercell Telecom',
                ),
                'Болгария' => array(
                    'sms.globul.bg' => 'Globul',
                    'sms.mtel.net' => 'M-Tel',
                ),
                'Беларусь' => array(
                    'sms.velcom.by' => 'Velcom',
                    'sms.mts.by' => 'МТС (37529)',
                ),
                'Грузия' => array(
                    'sms.ge' => 'Geocell',
                    'sms.magtigsm.ge' => 'MAGTI (+99532)',
                ),
                'Казахстан' => array(
                    'sms.k-mobile.kz' => 'K-Mobile',
                    'sms.beeline.kz' => 'Билайн',
                    'sms.kcell.kz' => 'Kcell (7701)',
                ),
                'Латвия' => array(
                    'sms.baltcom.lv' => 'Baltcom GSM',
                    'sms.lmt.lv' => 'LMT (371)',
                    'sms.tele2.lv' => 'Tele2 (371)',
                ),
                'Литва' => array(
                    'sms.bite.lt' => 'Bite GSM',
                ),
                'Молдова' => array(
                    'daewoounitel.com' => 'Daewoo Unitel',
                    'sms.idknet.com' => 'IDC (562, 774, 777, 778)',
                ),
                'Польша' => array(
                    'sms.plus.pl' => 'Polkomtel Plus GSM (+4860)',
                ),
                'Россия' => array(
                    'sms.akos.ru' => 'Акос (+7902)',
                    'sms.bwc.ru' => 'БайкалВестКом (7902, 7908)',
                    'smsline.bashcell.com' => 'BashCell (90425, 90)',
                    'sms.beemail.ru' => 'Билайн (7)',
                    'sms.martelcom.ru' => 'Волга Телеком, Элайн GSM (7902)',
                    'volgogsm.ru' => 'Волгоград GSM (7)',
                    'sms.dti.ru' => 'Даль Телеком',
                    'sms.etk.ru' => 'Енисейтелеком (7901)',
                    'sms.megafondv.ru' => 'Мегафон Дальний Восток (7924)',
                    'sms.megafonmoscow.ru' => 'Мегафон Москва (7926)',
                    'sms.megafon-nn.ru' => 'Мегафон Н. Новгород (7920)',
                    'sms.mgsm.ru' => 'Мегафон Поволжье (7927)',
                    'sms.megafonsib.ru' => 'Мегафон Сибирь (7923)',
                    'sms.ugsm.ru' => 'Мегафон Урал (7922)',
                    'sms.megafoncenter.ru' => 'Мегафон Центр (7920)',
                    'mailsms.mobicomk.ru' => 'Мегафон Центр-Юг, Мегафон Кавказ (7928)',
                    'nwgsm.ru' => 'Мегафон Северо-Запад (7921, 7931)',
                    'sms.ycc.ru' => 'Мотив (7904, 7908, 790287, 343)',
                    'sms.mcc.ru' => 'МСС',
                    'imode.mts.ru' => 'МТС I-mode (8)',
                    'sms.mts.ru' => 'МТС (7095)',
                    'mtslife.ru' => 'МТС (7913)',
                    'komi.mts.ru' => 'МТС Коми (7912), Северный GSM (7902)',
                    'sms.omsk-gsm.ru' => 'МТС Омск (7902)',
                    'sms.kubangsm.ru' => 'МТС Кубань (7918)',
                    'fecs-900.khv.ru' => 'МТС Хабаровск (7902)',
                    'sms.uraltel.ru' => 'МТС Екатеринбург (79028, 79048, 79126)',
                    'amur.mts.ru' => 'МТС Амурская Область (79145, 79025)',
                    'uln.mts.ru' => 'МТС Ульяновск (7917)',
                    'samara.mts.ru' => 'МТС Самара (7917)',
                    'volgase.mts.ru' => 'МТС Поволжье (791)',
                    'sms.ncc.nnov.ru' => 'НСС (7904, 7902, 7908, 7950)',
                    'sms.vntc.ru' => 'НТК (7902557, 74232)',
                    'recom.ru' => 'Реком (7910, 7915)',
                    'mail.stmobile.ru' => 'Сахалин GSM (7333)',
                    'sform.ru' => 'Связьинформ',
                    'sms.smr.ru' => 'СМАРТС (79023, 79047)',
                    'sms.samara-gsm.ru' => 'СМАРТС Самара',
                    'mobile.smarts-gsm.ru' => 'СМАРТС Ульяновск(8902)',
                    'sms.pcom.ru' => 'Сонет (501)',
                    'sms.sotel.nnov.ru' => 'Сотел (477)',
                    'scs-900.ru' => 'ССС-900 (7913)',
                    'stekgsm.ru' => 'Стек GSM (7902)',
                    'tele2.sms.ru' => 'Теле2',
                    'sms.csk.ru' => 'Теле2 Иркутск (7904)',
                    'suct.ru' => 'Ютел',
                    'suct.uu.ru' => 'Юуст (790, 735)',
                    'extel-gsm.com' => 'Экстел GSM Калининград (90)',
                    'sms.shgsm.ru' => 'Шупашкар GSM (7902)',
                ),
                'Украина' => array(
                    'sms.umc.ua' => 'UMC (38050,38095)',
                    'sms.jeans.net.ua' => 'Jeans (38066)',
                    'sms.welcome2well.com' => 'WellCom (38068), Privat:Mobile (38068), MOBI (38068)',
                    'sms.kyivstar.net' => 'Kyivstar (38067,38097), Djuice (38097)',
                    'sms.gt.kiev.ua' => 'Golden Telecom Киев (38039, 38044)',
                    'sms.gt.com.ua' => 'Golden Telecom Одесса (38048)',
                    'sms.ekotel.com.ua' => 'Ekotel (38099)',
                    'sms.dcc.org.ua' => 'DCC (380)',
                ),
                'Эстония' => array(
                    'sms.emt.ee' => 'EMT, Mobil Telephone (3725), Tele2 (3725)',
                    'rle.ee' => 'Elisa (37256)',
                ),
            ),
        ));

        if (false === $this->container->get('security.context')->isGranted($this->container->getParameter('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', 'captcha', array('required' => true, 'label' => 'Код'));
        }

        $builder->add('submit', 'submit', array('label' => 'Отправить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'rename';
    }
}
