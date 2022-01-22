<?php

namespace App\Controller;

use App\Form\Type\Hash\HashType;
use App\Service\Hash;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Route("/hash")
 */
class HashController extends AbstractController
{
    /**
     * @Route("", name="hash_index")
     */
    public function indexAction(Request $request): Response
    {
        $hash = null;
        $form = $this->createForm(HashType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $hash = $this->getHash($data);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Hash/index.html.twig', [
            'form' => $form->createView(),
            'result' => $hash,
        ]);
    }

    /**
     * @throws ValidatorException
     */
    private function getHash(array $data): string
    {
        /** @var Hash $hash */
        $hash = $this->container->get(Hash::class);

        $algorithms = $hash->getAlgorithms();
        $algorithm = $algorithms[$data['algorithm']];

        if (null !== $data['text']) {
            return $hash->hashString($algorithm, $data['text']);
        }
        if (null !== $data['file']) {
            return $hash->hashFile($algorithm, $data['file']);
        }

        throw new ValidatorException('Не заполнено ни одного поля с хэшируемыми данными');
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Hash::class] = '?'.Hash::class;

        return $services;
    }
}
