<?php

namespace App\Controller;

use App\Entity\Parameters;
use App\Form\ParametersType;
use App\Repository\ParametersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParametersController extends AbstractController
{
    #[Route('/parameters/new', name: 'app_parameters_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ParametersRepository $parametersRepository,
    ): Response
    {
        $parameter = new Parameters();
        $form = $this->createForm(ParametersType::class, $parameter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();

            $parameter
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
            ;

            $entityManager->persist($parameter);
            $entityManager->flush();

            $this->addFlash('success', 'Parametrul a fost adăugat cu succes.');

            return $this->redirectToRoute('app_parameters_new');
        }

        return $this->render('parameters/new.html.twig', [
            'form' => $form,
            'parameters' => $parametersRepository->findBy([], ['name' => 'ASC']),
            'page_title' => 'Parametru nou',
            'page_description' => 'Adăugați un parametru și tipul valorii acceptate.',
            'editing_parameter_id' => null,
        ]);
    }

    #[Route('/parameters/{id}/edit', name: 'app_parameters_edit', methods: ['GET', 'POST'])]
    public function edit(
        Parameters $parameter,
        Request $request,
        EntityManagerInterface $entityManager,
        ParametersRepository $parametersRepository,
    ): Response {
        $form = $this->createForm(ParametersType::class, $parameter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parameter->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Parametrul a fost actualizat cu succes.');

            return $this->redirectToRoute('app_parameters_new');
        }

        return $this->render('parameters/new.html.twig', [
            'form' => $form,
            'parameters' => $parametersRepository->findBy([], ['id' => 'DESC']),
            'page_title' => 'Editare parametru',
            'page_description' => 'Actualizați valorile parametrului selectat.',
            'editing_parameter_id' => $parameter->getId(),
        ]);
    }
}
