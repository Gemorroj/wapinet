<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
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

    public function searchAction(Request $request)
    {
        $form = $this->createForm(new SearchType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    //

                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:File:search.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function categoriesAction()
    {
        return $this->render('WapinetBundle:File:categories.html.twig');
    }

    public function listAction($page = 1, $date)
    {
        $query = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->getListBuilder($date);
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:File:list.html.twig', array('files' => $pagerfanta));
    }

    public function viewAction($id)
    {
        return $this->render('WapinetBundle:File:view.html.twig');
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
                    return new RedirectResponse(
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
        $data->setMimeType($data->getFile()->getMimeType());

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
