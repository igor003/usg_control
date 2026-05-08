<?php

namespace App\Controller;

use App\Entity\Patients;
use App\Form\PatientsType;
use App\Repository\PatientsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PatientsController extends AbstractController
{
    #[Route('/patients', name: 'app_patients_index', methods: ['GET'])]
    public function index(PatientsRepository $patientsRepository): Response
    {
        return $this->render('patients/index.html.twig', [
            'patients' => $this->findPatientsWithLocation($patientsRepository),
        ]);
    }

    #[Route('/patients/new', name: 'app_patients_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        PatientsRepository $patientsRepository,
    ): Response
    {
        $patient = new Patients();
        $idnp = trim((string) $request->query->get('idnp', ''));

        if (preg_match('/^\d{13}$/', $idnp)) {
            $patient->setIdnp($idnp);
        }

        $form = $this->createForm(PatientsType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $patient
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTimeImmutable())
            ;

            $entityManager->persist($patient);
            $entityManager->flush();

            $this->addFlash('success', 'Pacientul a fost adăugat cu succes.');

            return $this->redirectToRoute('app_patients_index');
        }

        return $this->render('patients/new.html.twig', [
            'form' => $form,
            'patients' => $this->findPatientsWithLocation($patientsRepository),
            'page_title' => 'Pacient nou',
            'page_description' => 'Adăugați datele pacientului înainte de crearea examinării.',
            'editing_patient_id' => null,
        ]);
    }

    #[Route('/patients/{id}/edit', name: 'app_patients_edit', methods: ['GET', 'POST'])]
    public function edit(
        Patients $patient,
        Request $request,
        EntityManagerInterface $entityManager,
        PatientsRepository $patientsRepository,
    ): Response {
        $form = $this->createForm(PatientsType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $patient->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Pacientul a fost actualizat cu succes.');

            return $this->redirectToRoute('app_patients_index');
        }

        return $this->render('patients/new.html.twig', [
            'form' => $form,
            'patients' => $this->findPatientsWithLocation($patientsRepository),
            'page_title' => 'Editare pacient',
            'page_description' => 'Actualizați datele pacientului selectat.',
            'editing_patient_id' => $patient->getId(),
        ]);
    }

    private function findPatientsWithLocation(PatientsRepository $patientsRepository): array
    {
        return $patientsRepository
            ->createQueryBuilder('p')
            ->leftJoin('p.city', 'c')
            ->addSelect('c')
            ->leftJoin('c.district', 'd')
            ->addSelect('d')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
