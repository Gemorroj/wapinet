<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MobileCodeController extends Controller
{
    public function indexAction()
    {
        return $this->render('@Wapinet/MobileCode/index.html.twig');
    }

    public function allPhonesAction()
    {
        return $this->render('@Wapinet/MobileCode/all_phones.html.twig');
    }

    public function androidAction()
    {
        return $this->render('@Wapinet/MobileCode/android.html.twig');
    }

    public function iphoneAction()
    {
        return $this->render('@Wapinet/MobileCode/iphone.html.twig');
    }

    public function alcatelAction()
    {
        return $this->render('@Wapinet/MobileCode/alcatel.html.twig');
    }

    public function boschAction()
    {
        return $this->render('@Wapinet/MobileCode/bosch.html.twig');
    }

    public function ericssonAction()
    {
        return $this->render('@Wapinet/MobileCode/ericsson.html.twig');
    }

    public function lgAction()
    {
        return $this->render('@Wapinet/MobileCode/lg.html.twig');
    }

    public function maxonAction()
    {
        return $this->render('@Wapinet/MobileCode/maxon.html.twig');
    }

    public function motorolaAction()
    {
        return $this->render('@Wapinet/MobileCode/motorola.html.twig');
    }

    public function nokiaAction()
    {
        return $this->render('@Wapinet/MobileCode/nokia.html.twig');
    }

    public function panasonicAction()
    {
        return $this->render('@Wapinet/MobileCode/panasonic.html.twig');
    }

    public function pantechAction()
    {
        return $this->render('@Wapinet/MobileCode/pantech.html.twig');
    }

    public function philipsAction()
    {
        return $this->render('@Wapinet/MobileCode/philips.html.twig');
    }

    public function sagemAction()
    {
        return $this->render('@Wapinet/MobileCode/sagem.html.twig');
    }

    public function samsungAction()
    {
        return $this->render('@Wapinet/MobileCode/samsung.html.twig');
    }

    public function siemensAction()
    {
        return $this->render('@Wapinet/MobileCode/siemens.html.twig');
    }

    public function simCardAction()
    {
        return $this->render('@Wapinet/MobileCode/sim_card.html.twig');
    }

    public function sonyEricssonAction()
    {
        return $this->render('@Wapinet/MobileCode/sony_ericsson.html.twig');
    }

    public function mitsubishiTriumAction()
    {
        return $this->render('@Wapinet/MobileCode/mitsubishi_trium.html.twig');
    }
}
