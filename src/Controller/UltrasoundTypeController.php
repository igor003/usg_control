<?php

namespace App\Controller;

use App\Entity\UltrasoundType;
use App\Form\UltrasoundTypeType;
use App\Repository\UltrasoundTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UltrasoundTypeController extends AbstractController
{
    #[Route('/ultrasound-types/new', name: 'app_ultrasound_types_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UltrasoundTypeRepository $ultrasoundTypeRepository,
    ): Response {
        $ultrasoundType = new UltrasoundType();
        $form = $this->createForm(UltrasoundTypeType::class, $ultrasoundType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();

            $ultrasoundType
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
            ;

            $entityManager->persist($ultrasoundType);
            $entityManager->flush();

            $this->addFlash('success', 'Tipul UZI a fost adăugat cu succes.');

            return $this->redirectToRoute('app_ultrasound_types_new');
        }

        return $this->render('ultrasound_types/new.html.twig', [
            'form' => $form,
            'ultrasound_types' => $ultrasoundTypeRepository->findBy([], ['sort_order' => 'ASC', 'name' => 'ASC', 'id' => 'ASC']),
            'page_title' => 'Tip UZI nou',
            'page_description' => 'Adăugați un tip de investigație UZI pentru organe.',
            'editing_ultrasound_type_id' => null,
        ]);
    }

    #[Route('/ultrasound-types/{id}/edit', name: 'app_ultrasound_types_edit', methods: ['GET', 'POST'])]
    public function edit(
        UltrasoundType $ultrasoundType,
        Request $request,
        EntityManagerInterface $entityManager,
        UltrasoundTypeRepository $ultrasoundTypeRepository,
    ): Response {
        $form = $this->createForm(UltrasoundTypeType::class, $ultrasoundType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ultrasoundType->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Tipul UZI a fost actualizat cu succes.');

            return $this->redirectToRoute('app_ultrasound_types_new');
        }

        return $this->render('ultrasound_types/new.html.twig', [
            'form' => $form,
            'ultrasound_types' => $ultrasoundTypeRepository->findBy([], ['sort_order' => 'ASC', 'name' => 'ASC', 'id' => 'ASC']),
            'page_title' => 'Editare tip UZI',
            'page_description' => 'Actualizați tipul UZI selectat.',
            'editing_ultrasound_type_id' => $ultrasoundType->getId(),
        ]);
    }
}
