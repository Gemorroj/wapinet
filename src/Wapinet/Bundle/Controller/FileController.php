<?php

namespace Wapinet\Bundle\Controller;

use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Wapinet\Bundle\Entity\File;
use Wapinet\Bundle\Form\Type\File\SearchType;
use Wapinet\Bundle\Form\Type\File\UploadType;

/**
 * @see http://wap4file.org
 */
class FileController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:File:index.html.twig');
    }

    public function informationAction()
    {
        return $this->render('WapinetBundle:File:information.html.twig');
    }

    public function statisticAction()
    {
        return $this->render('WapinetBundle:File:statistic.html.twig');
    }

    /**
     * @param Request $request
     * @param int $page
     * @param string|null $key
     * @return Response|RedirectResponse
     */
    public function searchAction(Request $request, $page = 1, $key = null)
    {
        $form = $this->createForm(new SearchType());
        $pagerfanta = null;
        $session = $this->get('session');

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = uniqid();
                    $session->set('file_search', array(
                        'key' => $key,
                        'data' => $data
                    ));
                }

                return $this->redirect(
                    $this->get('router')->generate('file_search', array('key' => $key), Router::ABSOLUTE_URL)
                );
            }

            if (null !== $key && true === $session->has('file_search')) {
                $search = $session->get('file_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->search($search['data'], $page);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:File:search.html.twig', array(
            'form' => $form->createView(),
            'files' => $pagerfanta,
            'key' => $key,
        ));
    }

    /**
     * @param array $data
     * @param int $page
     * @return Pagerfanta
     */
    protected function search (array $data, $page = 1)
    {
        $query = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->getSearchQuery(
            $data['search'],
            $data['use_description'],
            $data['categories'],
            $data['created_after'],
            $data['created_before']
        );

        return $this->get('paginate')->paginate($query, $page);
    }

    public function categoriesAction()
    {
        return $this->render('WapinetBundle:File:categories.html.twig');
    }

    /**
     * @param int $page
     * @param string|null $date
     * @param string|null $category
     * @return Response
     */
    public function listAction($page = 1, $date = null, $category = null)
    {
        $query = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->getListQuery($date, $category);
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:File:list.html.twig', array(
            'files' => $pagerfanta,
            'date' => $date,
            'category' => $category,
        ));
    }

    /**
     * @param int $id
     * @return Response
     */
    public function viewAction($id)
    {
        $file = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->find($id);

        return $this->render('WapinetBundle:File:view.html.twig', array('file' => $file));
    }

    /**
     * @param Request $request
     * @param int $id
     * @param string $name
     * @return BinaryFileResponse
     */
    public function archiveDownloadFileAction(Request $request, $id, $name)
    {
        $path = $request->get('path');
        $tmpDir = $this->get('kernel')->getTmpFileDir();

        $entry = $tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', '/', $path);

        if (true !== file_exists($entry)) {
            $file = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->find($id);
            $archive = $this->get('archive_7z');

            $archive->extractEntry($file->getFile(), $path, $tmpDir);
        }

        return new BinaryFileResponse($entry);
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function uploadAction(Request $request)
    {
        $form = $this->createForm(new UploadType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $file = $this->setFileData($request, $data);

                    $router = $this->container->get('router');
                    return $this->redirect(
                        $router->generate('file_view', array(
                                'id' => $file->getId()
                            ), Router::ABSOLUTE_URL
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:File:upload.html.twig', array(
            'form' => $form->createView()
        ));
    }


    /**
     * @param File $file
     */
    protected function saveFile(File $file)
    {
        // сохраняем в БД
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($file);
        $entityManager->flush();

        $this->saveFileAcl($file);
    }


    /**
     * @param File $file
     * @see http://symfony.com/doc/current/cookbook/security/acl.html
     */
    protected function saveFileAcl(File $file)
    {
        $user = $this->getUser();
        if (null !== $user) {
            // creating the ACL
            $aclProvider = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($file);
            $acl = $aclProvider->createAcl($objectIdentity);

            // retrieving the security identity of the currently logged-in user
            $securityIdentity = UserSecurityIdentity::fromAccount($user);

            // grant owner access
            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);
        }
    }


    /**
     * @param Request $request
     * @param File    $data
     *
     * @return File
     */
    protected function setFileData(Request $request, File $data)
    {
        $data->setUser($this->getUser());
        $data->setIp($request->getClientIp());
        $data->setBrowser($request->headers->get('User-Agent', ''));

        /** @var UploadedFile $file */
        $file = $data->getFile();
        $data->setMimeType($this->get('mime')->getMimeType($file->getClientOriginalName()));

        if (null !== $data->getPassword()) {
            $data->setSaltValue();

            $encoder = $this->get('security.encoder_factory')->getEncoder($data);
            $password = $encoder->encodePassword($data->getPassword(), $data->getSalt());
            $data->setPassword($password);
        }

        $this->saveFile($data);

        return $data;
    }
}
