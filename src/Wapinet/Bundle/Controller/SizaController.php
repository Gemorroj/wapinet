<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wapinet\Bundle\Form\Type\Siza\SearchType;


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
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function searchAction(Request $request)
    {
        $form = $this->createForm(new SearchType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $siza = $this->get('siza');
                    $searchId = $siza->getSearchId($data['search']);
                    if (false === $searchId) {
                        throw new \Exception('По Вашему запросу ничего не найдено.');
                    }

                    return $this->redirectToRoute('siza_search_list', array(
                        'searchId' => $searchId,
                        'searchQuery' => $data['search'],
                    ));
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Siza:search.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * @param Request $request
     * @param int    $searchId
     *
     * @return Response
     */
    public function searchListAction(Request $request, $searchId)
    {
        $q = $request->get('q', '/search/');
        if ('/search/' !== $q) {
            return $this->indexAction($request);
        }

        $page = $request->get('page');
        $scr = $request->get('scr');

        $searchQuery = $request->get('searchQuery');

        $siza = $this->get('siza');
        $siza->init('/bundles/wapinet/siza', $q, $page, $scr, $searchId);

        $content = $siza->getContentListSearch();
        $pages = $siza->getListingNagivation(array('searchQuery' => $searchQuery));

        return $this->render('WapinetBundle:Siza:search_list.html.twig', array(
            'content' => $content,
            'pages' => $pages,
            'name' => $searchQuery,
        ));
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
        return $this->redirect('http://load.siza.ru' . $query, 301);
    }
}
