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
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('WapinetBundle:Icq:index.html.twig');
    }

    /**
     * @return array
     */
    protected function getIcqVariables ()
    {
        $curl = $this->get('curl');
        $curl->init('http://www.icq.com/join/ru');
        $curl->addBrowserHeaders();
        $curl->addCompression();
        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        \preg_match('/name="csrf"[\s+]value="(.+)"/U', $response->getContent(), $csrf);
        \preg_match('/name="gnm"[\s+]value="(.+)"/U', $response->getContent(), $gnm);
        \preg_match('/src="https:\/\/www\.icq\.com\/utils\/recaptcha\/gnm\/(.+)"/U', $response->getContent(), $img_gnm);

        return array(
            'csrf' => $csrf[1],
            'gnm' => $gnm[1],
            'gnm_img' => $img_gnm[1],
            'reg_type' => '2',
        );
    }

    /**
     * @param array $data
     *
     * @throws \RuntimeException
     */
    protected function icqRegistration (array $data)
    {
        $curl = $this->get('curl');
        $curl->init('http://www.icq.com/join/commit/ru');
        $curl->addBrowserHeaders();
        $curl->addCompression();
        $curl->addHeader('Referer', 'http://www.icq.com/join/ru');
        $curl->addHeader('X-Requested-With', 'XMLHttpRequest');
        $curl->addHeader('Cookie', 'is_ab_mim=0; rfd=; icq_tracking=' . \mt_rand(99999, \PHP_INT_MAX) . '; icq_lang=ru; csrf=' . $data['csrf']);

        $curl->addPostData('reg_type', $data['reg_type']);
        $curl->addPostData('csrf', $data['csrf']);
        $curl->addPostData('first_name', $data['first_name']);
        $curl->addPostData('last_name', $data['last_name']);
        $curl->addPostData('email', $data['email']);
        $curl->addPostData('password', $data['password']);
        $curl->addPostData('gnm', $data['gnm']);
        $curl->addPostData('captcha', $data['captcha']);

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $json = json_decode($response->getContent());

        if (200 !== $json->status) {
            throw new \RuntimeException('Проверьте правильность данных и попробуйте еще раз. Возможно, указанный E-mail уже был зарегистрирован.');
        }
    }


    /**
     * @param Request $request
     * @return Response
     */
    public function registrationAction(Request $request)
    {
        $result = null;
        $variables = null;

        $form = $this->createForm(new RegistrationType());
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $this->icqRegistration($data);
                    $result = true;
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        if (true !== $result) {
            $variables = $this->getIcqVariables();
        }

        return $this->render('WapinetBundle:Icq:registration.html.twig', array(
            'form' => $form->createView(),
            'variables' => $variables,
            'result' => $result
        ));
    }

    /**
     * @param string $gnm_img
     * @return Response
     */
    public function registrationPicAction($gnm_img)
    {
        $curl = $this->get('curl');
        $curl->init('https://www.icq.com/utils/recaptcha/gnm/' . $gnm_img);
        $curl->addBrowserHeaders();
        $curl->addHeader('Referer', 'http://www.icq.com/join/ru');
        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $im = @\imagecreatefromstring($response->getContent());
        if ($im === false) {
            return new Response('Error');
        }

        $i = \imagecreatetruecolor(120, 50);
        \imagecopyresampled($i, $im, 0, 0, 0, 0, 120, 50, 240, 100);

        //\imageinterlace($im, 1); // Примочка

        \ob_start();
        \imagepng($i, null, 9);
        $image = \ob_get_contents();
        \ob_end_clean();
        \imagedestroy($i);
        \imagedestroy($im);


        $response = new Response($image);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0');

        return $response;
    }

    /**
     * @return Response
     */
    public function aboutAction()
    {
        return $this->render('WapinetBundle:Icq:about.html.twig');
    }

    /**
     * @return Response
     */
    public function inviseAction()
    {
        return $this->render('WapinetBundle:Icq:invise.html.twig');
    }

    /**
     * @return Response
     */
    public function secureAction()
    {
        return $this->render('WapinetBundle:Icq:secure.html.twig');
    }

    /**
     * @return Response
     */
    public function serversAction()
    {
        return $this->render('WapinetBundle:Icq:servers.html.twig');
    }

    /**
     * @return Response
     */
    public function clientsAction()
    {
        return $this->render('WapinetBundle:Icq:clients.html.twig');
    }

    /**
     * @return Response
     */
    public function servicesAction()
    {
        return $this->render('WapinetBundle:Icq:services.html.twig');
    }

    /**
     * @return Response
     */
    public function disconnectAction()
    {
        return $this->render('WapinetBundle:Icq:disconnect.html.twig');
    }

    /**
     * @return Response
     */
    public function errorsAction()
    {
        return $this->render('WapinetBundle:Icq:errors.html.twig');
    }

    /**
     * @return Response
     */
    public function searchAction()
    {
        return $this->render('WapinetBundle:Icq:search.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function userInfoAction(Request $request)
    {
        $result = null;

        $form = $this->createForm(new UserInfoType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $content = $this->cleanUserInfo($data['uin']);
                    $result = array(
                        'uin' => $data['uin'],
                        'content' => $content, //raw
                    );
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Icq:user_info.html.twig', array(
            'form' => $form->createView(),
            'result' => $result
        ));
    }


    /**
     * @param string $uin
     *
     * @throws \RuntimeException
     * @return string
     */
    protected function cleanUserInfo($uin)
    {
        $curl = $this->get('curl');
        $curl->init('http://www.icq.com/people/' . $uin . '/view/ru');
        $curl->addBrowserHeaders();
        $curl->addCompression();
        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $out = \explode('<div class="form-col l">', $response->getContent());
        if (!isset($out[1])) {
            return '';
        }
        $out = '<div><div><div class="form-col l">' . $out[1];
        $out = \explode('</div></div>', $out);
        $out = $out[0];

        $out = \str_replace('<label for="" class="l">', '<label>', $out);
        $out = \str_replace('</label>', ':</label>', $out);
        $out = \str_replace('<div class="clearBoth"></div>', '', $out);
        $out = \str_replace('<hr />', '', $out);

        if (!$out) {
            throw new \RuntimeException('Пользователь не найден');
        }

        return $out;
    }
}
