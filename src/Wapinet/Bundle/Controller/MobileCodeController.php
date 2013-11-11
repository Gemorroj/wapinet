<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MobileCodeController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:MobileCode:index.html.twig');
    }

    public function allPhonesAction()
    {
        return $this->render('WapinetBundle:MobileCode:all_phones.html.twig');
    }

    public function androidAction()
    {
        return $this->render('WapinetBundle:MobileCode:android.html.twig');
    }

    public function iphoneAction()
    {
        return $this->render('WapinetBundle:MobileCode:iphone.html.twig');
    }

    public function alcatelAction()
    {
        return $this->render('WapinetBundle:MobileCode:alcatel.html.twig');
    }

    public function boschAction()
    {
        return $this->render('WapinetBundle:MobileCode:bosch.html.twig');
    }

    public function ericssonAction()
    {
        return $this->render('WapinetBundle:MobileCode:ericsson.html.twig');
    }

    public function lgAction()
    {
        return $this->render('WapinetBundle:MobileCode:lg.html.twig');
    }

    public function maxonAction()
    {
        return $this->render('WapinetBundle:MobileCode:maxon.html.twig');
    }

    public function motorolaAction()
    {
        return $this->render('WapinetBundle:MobileCode:motorola.html.twig');
    }

    public function nokiaAction()
    {
        return $this->render('WapinetBundle:MobileCode:nokia.html.twig');
    }

    public function panasonicAction()
    {
        return $this->render('WapinetBundle:MobileCode:panasonic.html.twig');
    }

    public function pantechAction()
    {
        return $this->render('WapinetBundle:MobileCode:pantech.html.twig');
    }

    public function philipsAction()
    {
        return $this->render('WapinetBundle:MobileCode:philips.html.twig');
    }

    public function sagemAction()
    {
        return $this->render('WapinetBundle:MobileCode:sagem.html.twig');
    }

    public function samsungAction()
    {
        return $this->render('WapinetBundle:MobileCode:samsung.html.twig');
    }

    public function siemensAction()
    {
        return $this->render('WapinetBundle:MobileCode:siemens.html.twig');
    }

    public function simCardAction()
    {
        return $this->render('WapinetBundle:MobileCode:sim_card.html.twig');
    }

    public function sonyEricssonAction()
    {
        return $this->render('WapinetBundle:MobileCode:sony_ericsson.html.twig');
    }

    public function mitsubishiTriumAction()
    {
        return $this->render('WapinetBundle:MobileCode:mitsubishi_trium.html.twig');
    }
}
