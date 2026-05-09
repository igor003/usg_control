<?php

namespace App\Controller;

use App\Entity\Organs;
use App\Form\OrgansType;
use App\Repository\OrgansRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class OrgansController extends AbstractController
{
    #[Route('/organs/new', name: 'app_organs_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        OrgansRepository $organsRepository,
        SluggerInterface $slugger,
    ): Response {
        $organ = new Organs();
        $form = $this->createForm(OrgansType::class, $organ);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            $imageError = $this->validateImageFile($imageFile);

            if ($imageError !== null) {
                $form->get('imageFile')->addError(new FormError($imageError));

                return $this->render('organs/new.html.twig', [
                    'form' => $form,
                    'organs' => $this->findOrgansForList($organsRepository),
                    'page_title' => 'Organ nou',
                    'page_description' => 'Adăugați organul și imaginea utilizată în lista pentru examinare.',
                    'editing_organ_id' => null,
                    'current_image_path' => null,
                ]);
            }

            $now = new \DateTimeImmutable();

            $organ
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
            ;

            $this->handleImageUpload($imageFile, $organ, $slugger);

            $entityManager->persist($organ);
            $entityManager->flush();

            $this->addFlash('success', 'Organul a fost adăugat cu succes.');

            return $this->redirectToRoute('app_organs_new');
        }

        return $this->render('organs/new.html.twig', [
            'form' => $form,
            'organs' => $this->findOrgansForList($organsRepository),
            'page_title' => 'Organ nou',
            'page_description' => 'Adăugați organul și imaginea utilizată în lista pentru examinare.',
            'editing_organ_id' => null,
            'current_image_path' => null,
        ]);
    }

    #[Route('/organs/{id}/edit', name: 'app_organs_edit', methods: ['GET', 'POST'])]
    public function edit(
        Organs $organ,
        Request $request,
        EntityManagerInterface $entityManager,
        OrgansRepository $organsRepository,
        SluggerInterface $slugger,
    ): Response {
        $form = $this->createForm(OrgansType::class, $organ);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            $imageError = $this->validateImageFile($imageFile);

            if ($imageError !== null) {
                $form->get('imageFile')->addError(new FormError($imageError));

                return $this->render('organs/new.html.twig', [
                    'form' => $form,
                    'organs' => $this->findOrgansForList($organsRepository),
                    'page_title' => 'Editare organ',
                    'page_description' => 'Actualizați organul selectat.',
                    'editing_organ_id' => $organ->getId(),
                    'current_image_path' => $organ->getImagePath(),
                ]);
            }

            $organ->setUpdatedAt(new \DateTimeImmutable());
            $this->handleImageUpload($imageFile, $organ, $slugger);
            $entityManager->flush();

            $this->addFlash('success', 'Organul a fost actualizat cu succes.');

            return $this->redirectToRoute('app_organs_new');
        }

        return $this->render('organs/new.html.twig', [
            'form' => $form,
            'organs' => $this->findOrgansForList($organsRepository),
            'page_title' => 'Editare organ',
            'page_description' => 'Actualizați organul selectat.',
            'editing_organ_id' => $organ->getId(),
            'current_image_path' => $organ->getImagePath(),
        ]);
    }

    private function handleImageUpload(?UploadedFile $imageFile, Organs $organ, SluggerInterface $slugger): void
    {
        if (!$imageFile instanceof UploadedFile) {
            return;
        }

        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename)->lower();
        $extension = strtolower($imageFile->getClientOriginalExtension());
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $extension);
        $relativePath = 'uploads/organs/' . $newFilename;

        $imageFile->move(
            (string) $this->getParameter('kernel.project_dir') . '/public/uploads/organs',
            $newFilename,
        );

        $organ->setImagePath($relativePath);
    }

    private function validateImageFile(?UploadedFile $imageFile): ?string
    {
        if (!$imageFile instanceof UploadedFile) {
            return null;
        }

        $extension = strtolower($imageFile->getClientOriginalExtension());
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return 'Încărcați o imagine PNG, JPG sau WebP.';
        }

        if (@getimagesize($imageFile->getPathname()) === false) {
            return 'Fișierul încărcat nu este o imagine validă.';
        }

        return null;
    }

    /**
     * @return Organs[]
     */
    private function findOrgansForList(OrgansRepository $organsRepository): array
    {
        return $organsRepository
            ->createQueryBuilder('o')
            ->leftJoin('o.ultrasound_type', 'ut')
            ->addSelect('ut')
            ->orderBy('ut.sort_order', 'ASC')
            ->addOrderBy('ut.name', 'ASC')
            ->addOrderBy('o.sort_order', 'ASC')
            ->addOrderBy('o.name', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
