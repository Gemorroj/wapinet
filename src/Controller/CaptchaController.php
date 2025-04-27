<?php

namespace App\Controller;

use App\Service\CaptchaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_captcha')]
class CaptchaController extends AbstractController
{
    #[Route(path: '', name: 'app.captcha')]
    public function captchaAction(Request $request, CaptchaService $captchaService): StreamedResponse
    {
        $phrase = $captchaService->generatePhrase(5);
        $session = $request->getSession();
        $session->set(CaptchaService::SESSION_KEY, $phrase);
        $gd = $captchaService->generateGd($phrase);

        return new StreamedResponse(static function () use ($gd): void {
            \imagejpeg($gd, null, 75);
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/jpeg',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
