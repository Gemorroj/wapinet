<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mobile_code')]
class MobileCodeController extends AbstractController
{
    #[Route(path: '', name: 'mobile_code_index')]
    public function indexAction(): Response
    {
        return $this->render('MobileCode/index.html.twig');
    }

    #[Route(path: '/all_phones', name: 'mobile_code_all_phones')]
    public function allPhonesAction(): Response
    {
        return $this->render('MobileCode/all_phones.html.twig');
    }

    #[Route(path: '/android', name: 'mobile_code_android')]
    public function androidAction(): Response
    {
        return $this->render('MobileCode/android.html.twig');
    }

    #[Route(path: '/iphone', name: 'mobile_code_iphone')]
    public function iphoneAction(): Response
    {
        return $this->render('MobileCode/iphone.html.twig');
    }

    #[Route(path: '/alcatel', name: 'mobile_code_alcatel')]
    public function alcatelAction(): Response
    {
        return $this->render('MobileCode/alcatel.html.twig');
    }

    #[Route(path: '/bosch', name: 'mobile_code_bosch')]
    public function boschAction(): Response
    {
        return $this->render('MobileCode/bosch.html.twig');
    }

    #[Route(path: '/ericsson', name: 'mobile_code_ericsson')]
    public function ericssonAction(): Response
    {
        return $this->render('MobileCode/ericsson.html.twig');
    }

    #[Route(path: '/lg', name: 'mobile_code_lg')]
    public function lgAction(): Response
    {
        return $this->render('MobileCode/lg.html.twig');
    }

    #[Route(path: '/maxon', name: 'mobile_code_maxon')]
    public function maxonAction(): Response
    {
        return $this->render('MobileCode/maxon.html.twig');
    }

    #[Route(path: '/motorola', name: 'mobile_code_motorola')]
    public function motorolaAction(): Response
    {
        return $this->render('MobileCode/motorola.html.twig');
    }

    #[Route(path: '/nokia', name: 'mobile_code_nokia')]
    public function nokiaAction(): Response
    {
        return $this->render('MobileCode/nokia.html.twig');
    }

    #[Route(path: '/panasonic', name: 'mobile_code_panasonic')]
    public function panasonicAction(): Response
    {
        return $this->render('MobileCode/panasonic.html.twig');
    }

    #[Route(path: '/pantech', name: 'mobile_code_pantech')]
    public function pantechAction(): Response
    {
        return $this->render('MobileCode/pantech.html.twig');
    }

    #[Route(path: '/philips', name: 'mobile_code_philips')]
    public function philipsAction(): Response
    {
        return $this->render('MobileCode/philips.html.twig');
    }

    #[Route(path: '/sagem', name: 'mobile_code_sagem')]
    public function sagemAction(): Response
    {
        return $this->render('MobileCode/sagem.html.twig');
    }

    #[Route(path: '/samsung', name: 'mobile_code_samsung')]
    public function samsungAction(): Response
    {
        return $this->render('MobileCode/samsung.html.twig');
    }

    #[Route(path: '/siemens', name: 'mobile_code_siemens')]
    public function siemensAction(): Response
    {
        return $this->render('MobileCode/siemens.html.twig');
    }

    #[Route(path: '/sim_card', name: 'mobile_code_sim_card')]
    public function simCardAction(): Response
    {
        return $this->render('MobileCode/sim_card.html.twig');
    }

    #[Route(path: '/sony_ericsson', name: 'mobile_code_sony_ericsson')]
    public function sonyEricssonAction(): Response
    {
        return $this->render('MobileCode/sony_ericsson.html.twig');
    }

    #[Route(path: '/mitsubishi_trium', name: 'mobile_code_mitsubishi_trium')]
    public function mitsubishiTriumAction(): Response
    {
        return $this->render('MobileCode/mitsubishi_trium.html.twig');
    }
}
