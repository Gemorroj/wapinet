<?php

namespace App\Controller;

use App\Form\Type\Hash\HashType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidatorException;

class HashController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
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
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Hash/index.html.twig', [
            'form' => $form->createView(),
            'result' => $hash,
        ]);
    }

    /**
     * @param array $data
     *
     * @throws ValidatorException
     *
     * @return string
     */
    protected function getHash(array $data)
    {
        $hash = $this->get('hash');
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
}
