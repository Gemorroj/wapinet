<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class SizaController extends Controller
{
    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function indexAction(Request $request)
    {
        $q = $request->get('q');
        $page = $request->get('page');
        $scr = $request->get('scr');
        $download = $request->get('download');
        $screen = $request->get('screen');

        if (empty($q)) {
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

    /**
     * @param string $query
     * @param int    $page
     * @param int    $scr
     *
     * @return Response
     */
    protected function show($query, $page = null, $scr = null)
    {
        $siza = $this->get('siza');
        $siza->init('/bundles/wapinet/siza', $query, $page, $scr);

        $content = '';
        $pages = '';

        if (0 === \strpos($query, '/artists/')) {
            $content .= $siza->getContent();
        } else {
            $content .= $siza->getContentList();
            $content .= $siza->getFoldersListDl();
            $content .= $siza->getFoldersList();
            $pages = $siza->getListingNagivation();
            if ('' === $content) {
                $content .= $siza->getContent();
            }
        }
        $breadcrumbs = $siza->getContentNavigator();
        $name = \array_pop($breadcrumbs);

        return $this->render('WapinetBundle:Siza:show.html.twig', array(
            'content' => $content,
            'breadcrumbs' => $breadcrumbs,
            'name' => $name['name'],
            'pages' => $pages,
        ));
    }


    /**
     * @param string $query
     *
     * @return RedirectResponse
     */
    protected function download($query)
    {
        return $this->redirect('http://f.siza.ru' . $query, 301);
    }


    /**
     * @param string $query
     *
     * @return RedirectResponse
     */
    protected function screen($query)
    {
        return $this->redirect('http://siza.ru' . $query, 301);
    }
}
