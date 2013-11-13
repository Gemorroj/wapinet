<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wapinet\Bundle\Form\Type\Icq\UserInfoType;
use Wapinet\Bundle\Form\Type\Icq\RegistrationType;
use Symfony\Component\Form\FormError;


class IcqController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Icq:index.html.twig');
    }

    public function registrationAction(Request $request)
    {
        $result = null;

        $form = $this->createForm(new RegistrationType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();
/*
                $content = $this->cleanUserInfo($data['uin']);
                if (!$content) {
                    $form->addError(new FormError('Пользователь не найден'));
                } else {
                    $result = array(
                        'uin' => $data['uin'],
                        'content' => $content, //raw
                    );
                }
*/
            }
        }

        return $this->render('WapinetBundle:Icq:registration.html.twig', array(
            'form' => $form->createView(),
            'gnm' => 'sdf',
            'gnm_img' => '3453',
            'csrf' => '111',
            'reg_type' => '2',
            'result' => $result
        ));
    }

    public function registrationPicAction(Request $request, $gnm_img)
    {
        $curl = $this->get('curl_helper');
        $curl->setOpt(CURLOPT_URL, 'https://www.icq.com/utils/recaptcha/gnm/' . $gnm_img);
        $curl->addBrowserHeaders();
        $curl->setOpt(CURLOPT_REFERER, 'http://www.icq.com/join/ru');
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_HEADER, false);
        $out = $curl->exec();

        $im = @imagecreatefromstring($out);
        if ($im === false) {
            return new Response('Error');
        }

        $i = imagecreatetruecolor(120, 50);
        imagecopyresampled($i, $im, 0, 0, 0, 0, 120, 50, 240, 100);

        //imageinterlace($im, 1); // Примочка

        ob_start();
        imagepng($i, null, 9);
        $image = ob_get_contents();
        ob_end_clean();
        imagedestroy($i);
        imagedestroy($im);


        $response = new Response($image);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0');

        return $response;
    }

    public function aboutAction()
    {
        return $this->render('WapinetBundle:Icq:about.html.twig');
    }

    public function inviseAction()
    {
        return $this->render('WapinetBundle:Icq:invise.html.twig');
    }

    public function secureAction()
    {
        return $this->render('WapinetBundle:Icq:secure.html.twig');
    }

    public function serversAction()
    {
        return $this->render('WapinetBundle:Icq:servers.html.twig');
    }

    public function clientsAction()
    {
        return $this->render('WapinetBundle:Icq:clients.html.twig');
    }

    public function servicesAction()
    {
        return $this->render('WapinetBundle:Icq:services.html.twig');
    }

    public function disconnectAction()
    {
        return $this->render('WapinetBundle:Icq:disconnect.html.twig');
    }

    public function errorsAction()
    {
        return $this->render('WapinetBundle:Icq:errors.html.twig');
    }

    public function searchAction()
    {
        return $this->render('WapinetBundle:Icq:search.html.twig');
    }

    public function userInfoAction(Request $request)
    {
        $result = null;

        $form = $this->createForm(new UserInfoType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                $content = $this->cleanUserInfo($data['uin']);
                if (!$content) {
                    $form->addError(new FormError('Пользователь не найден'));
                } else {
                    $result = array(
                        'uin' => $data['uin'],
                        'content' => $content, //raw
                    );
                }
            }
        }

        return $this->render('WapinetBundle:Icq:user_info.html.twig', array(
            'form' => $form->createView(),
            'result' => $result
        ));
    }


    /**
     * @param string $uin
     *
     * @return string
     */
    protected function cleanUserInfo($uin)
    {
        $curl = $this->get('curl_helper');
        $curl->setOpt(CURLOPT_URL, 'http://www.icq.com/people/' . $uin . '/view/ru');
        $curl->addBrowserHeaders();
        $curl->addCompression();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_AUTOREFERER, false);
        $curl->setOpt(CURLOPT_HEADER, false);
        $out = $curl->exec();

        $out = explode('<div class="form-col l">', $out);
        if (!isset($out[1])) {
            return '';
        }
        $out = '<div><div><div class="form-col l">' . $out[1];
        $out = explode('</div></div>', $out);
        $out = $out[0];

        $out = str_replace('<label for="" class="l">', '<label>', $out);
        $out = str_replace('</label>', ':</label>', $out);
        $out = str_replace('<div class="clearBoth"></div>', '', $out);
        $out = str_replace('<hr />', '', $out);

        return $out;
    }
}
