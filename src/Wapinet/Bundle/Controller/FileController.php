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

    public function statisticsAction()
    {
        return $this->render('WapinetBundle:File:statistics.html.twig');
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
                    /** @var File $data */
                    $data = $form->getData();
                    $data->setUser($this->getUser());
                    $data->setIp($request->getClientIp());
                    $data->setBrowser($request->headers->get('User-Agent', ''));
                    $data->setMimeType($data->getFile()->getMimeType());

                    $file = $this->saveFile($data);

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
     * @return File
     */
    protected function saveFile(File $file)
    {
        // сохраняем в БД
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($file);
        $entityManager->flush();

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

        return $file;
    }
}
