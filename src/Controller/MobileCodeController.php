<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MobileCodeController extends Controller
{
    public function indexAction()
    {
        return $this->render('MobileCode/index.html.twig');
    }

    public function allPhonesAction()
    {
        return $this->render('MobileCode/all_phones.html.twig');
    }

    public function androidAction()
    {
        return $this->render('MobileCode/android.html.twig');
    }

    public function iphoneAction()
    {
        return $this->render('MobileCode/iphone.html.twig');
    }

    public function alcatelAction()
    {
        return $this->render('MobileCode/alcatel.html.twig');
    }

    public function boschAction()
    {
        return $this->render('MobileCode/bosch.html.twig');
    }

    public function ericssonAction()
    {
        return $this->render('MobileCode/ericsson.html.twig');
    }

    public function lgAction()
    {
        return $this->render('MobileCode/lg.html.twig');
    }

    public function maxonAction()
    {
        return $this->render('MobileCode/maxon.html.twig');
    }

    public function motorolaAction()
    {
        return $this->render('MobileCode/motorola.html.twig');
    }

    public function nokiaAction()
    {
        return $this->render('MobileCode/nokia.html.twig');
    }

    public function panasonicAction()
    {
        return $this->render('MobileCode/panasonic.html.twig');
    }

    public function pantechAction()
    {
        return $this->render('MobileCode/pantech.html.twig');
    }

    public function philipsAction()
    {
        return $this->render('MobileCode/philips.html.twig');
    }

    public function sagemAction()
    {
        return $this->render('MobileCode/sagem.html.twig');
    }

    public function samsungAction()
    {
        return $this->render('MobileCode/samsung.html.twig');
    }

    public function siemensAction()
    {
        return $this->render('MobileCode/siemens.html.twig');
    }

    public function simCardAction()
    {
        return $this->render('MobileCode/sim_card.html.twig');
    }

    public function sonyEricssonAction()
    {
        return $this->render('MobileCode/sony_ericsson.html.twig');
    }

    public function mitsubishiTriumAction()
    {
        return $this->render('MobileCode/mitsubishi_trium.html.twig');
    }
}
