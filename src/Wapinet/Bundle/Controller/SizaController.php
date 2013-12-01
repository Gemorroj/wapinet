<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;


class SizaController extends Controller
{
    public function indexAction(Request $request)
    {
        $q = $request->get('q');
        $page = $request->get('page');
        $scr = $request->get('scr');
        $download = $request->get('download');
        $screen = $request->get('screen');

        if (null === $q) {
            return $this->render('WapinetBundle:Siza:index.html.twig');
        }
        if ('yes' === $download) {
            return $this->download($q);
        }
        if ('yes' === $screen) {
            return $this->screen($q);
        }

        return $this->show($q, $page, $scr);
    }

    protected function show($query, $page = null, $scr = null)
    {
        $siza = $this->get('siza');
        $siza->init('/bundles/wapinet/siza', $query, $page, $scr);

        $content = '';

        if (strpos($query, '/artists/') === 0) {
            $content .= $siza->getContent();
        } else {
            $content .= $siza->getContentList();
            $content .= $siza->getFoldersListDl();
            $content .= $siza->getFoldersList();
            $content .= $siza->getListingNagivation();
            if ($content === '') {
                $content .= $siza->getContent();
            }
        }
        $content .= $siza->getContentNavigator();

        return $this->render('WapinetBundle:Siza:show.html.twig', array(
            'content' => $content
        ));
    }


    protected function download($query)
    {
        return new RedirectResponse('http://f.siza.ru' . $query, 301);
    }

    protected function screen($query)
    {
        return new RedirectResponse('http://load.siza.ru' . $query, 301);
    }
}
