<?php

namespace App\Controller;

use App\Entity\Organs;
use App\Entity\UltrasoundType;
use App\Entity\UltrasoundTypeOrgan;
use App\Form\UltrasoundTypeType;
use App\Repository\OrgansRepository;
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
        OrgansRepository $organsRepository,
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

            $this->syncOrgans(
                $ultrasoundType,
                $this->readSelectedOrgans($request, $organsRepository),
                $entityManager,
            );

            $entityManager->persist($ultrasoundType);
            $entityManager->flush();

            $this->addFlash('success', 'Tipul USG a fost adăugat cu succes.');

            return $this->redirectToRoute('app_ultrasound_types_new');
        }

        return $this->render('ultrasound_types/new.html.twig', [
            'form' => $form,
            'ultrasound_types' => $this->findUltrasoundTypesForList($ultrasoundTypeRepository),
            'organs' => $organsRepository->findBy([], ['sort_order' => 'ASC', 'name' => 'ASC', 'id' => 'ASC']),
            'page_title' => 'Tip USG nou',
            'page_description' => 'Adăugați un tip de investigație USG pentru organe.',
            'editing_ultrasound_type_id' => null,
            'selected_organ_orders' => [],
        ]);
    }

    #[Route('/ultrasound-types/{id}/edit', name: 'app_ultrasound_types_edit', methods: ['GET', 'POST'])]
    public function edit(
        UltrasoundType $ultrasoundType,
        Request $request,
        EntityManagerInterface $entityManager,
        UltrasoundTypeRepository $ultrasoundTypeRepository,
        OrgansRepository $organsRepository,
    ): Response {
        $form = $this->createForm(UltrasoundTypeType::class, $ultrasoundType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ultrasoundType->setUpdatedAt(new \DateTimeImmutable());
            $this->syncOrgans(
                $ultrasoundType,
                $this->readSelectedOrgans($request, $organsRepository),
                $entityManager,
            );
            $entityManager->flush();

            $this->addFlash('success', 'Tipul USG a fost actualizat cu succes.');

            return $this->redirectToRoute('app_ultrasound_types_new');
        }

        return $this->render('ultrasound_types/new.html.twig', [
            'form' => $form,
            'ultrasound_types' => $this->findUltrasoundTypesForList($ultrasoundTypeRepository),
            'organs' => $organsRepository->findBy([], ['sort_order' => 'ASC', 'name' => 'ASC', 'id' => 'ASC']),
            'page_title' => 'Editare tip USG',
            'page_description' => 'Actualizați tipul USG selectat.',
            'editing_ultrasound_type_id' => $ultrasoundType->getId(),
            'selected_organ_orders' => $this->getSelectedOrganOrders($ultrasoundType),
        ]);
    }

    /**
     * @param array<int, array{organ: Organs, sortOrder: int}> $selectedOrgans
     */
    private function syncOrgans(
        UltrasoundType $ultrasoundType,
        array $selectedOrgans,
        EntityManagerInterface $entityManager,
    ): void {
        $existingByOrganId = [];

        foreach ($ultrasoundType->getUltrasoundTypeOrgans() as $ultrasoundTypeOrgan) {
            $organId = $ultrasoundTypeOrgan->getOrgan()?->getId();

            if ($organId !== null) {
                $existingByOrganId[$organId] = $ultrasoundTypeOrgan;
            }
        }

        $selectedIds = [];

        foreach ($selectedOrgans as $selectedOrgan) {
            $organ = $selectedOrgan['organ'];
            $organId = $organ->getId();

            if ($organId === null) {
                continue;
            }

            $selectedIds[] = $organId;
            $ultrasoundTypeOrgan = $existingByOrganId[$organId] ?? null;

            if (!$ultrasoundTypeOrgan instanceof UltrasoundTypeOrgan) {
                $ultrasoundTypeOrgan = (new UltrasoundTypeOrgan())
                    ->setUltrasoundType($ultrasoundType)
                    ->setOrgan($organ)
                ;

                $ultrasoundType->addUltrasoundTypeOrgan($ultrasoundTypeOrgan);
                $organ->addUltrasoundTypeOrgan($ultrasoundTypeOrgan);
                $entityManager->persist($ultrasoundTypeOrgan);
            }

            $ultrasoundTypeOrgan->setSortOrder($selectedOrgan['sortOrder']);
        }

        foreach ($existingByOrganId as $organId => $ultrasoundTypeOrgan) {
            if (!in_array($organId, $selectedIds, true)) {
                $ultrasoundType->removeUltrasoundTypeOrgan($ultrasoundTypeOrgan);
                $ultrasoundTypeOrgan->getOrgan()?->removeUltrasoundTypeOrgan($ultrasoundTypeOrgan);
                $entityManager->remove($ultrasoundTypeOrgan);
            }
        }
    }

    /**
     * @return array<int, array{organ: Organs, sortOrder: int}>
     */
    private function readSelectedOrgans(Request $request, OrgansRepository $organsRepository): array
    {
        $organIds = array_values(array_unique(array_filter(
            array_map('intval', $request->request->all('organ_ids')),
            static fn (int $organId): bool => $organId > 0,
        )));

        if ($organIds === []) {
            return [];
        }

        $orders = $request->request->all('organ_order');
        $organs = $organsRepository->findBy(['id' => $organIds]);
        $selectedOrgans = [];

        foreach ($organs as $organ) {
            $organId = $organ->getId();

            if ($organId === null) {
                continue;
            }

            $selectedOrgans[] = [
                'organ' => $organ,
                'sortOrder' => max(0, (int) ($orders[$organId] ?? 0)),
            ];
        }

        usort(
            $selectedOrgans,
            static fn (array $left, array $right): int => $left['sortOrder'] <=> $right['sortOrder']
        );

        return $selectedOrgans;
    }

    /**
     * @return array<int, int>
     */
    private function getSelectedOrganOrders(UltrasoundType $ultrasoundType): array
    {
        $selectedOrganOrders = [];

        foreach ($ultrasoundType->getUltrasoundTypeOrgans() as $ultrasoundTypeOrgan) {
            $organId = $ultrasoundTypeOrgan->getOrgan()?->getId();

            if ($organId !== null) {
                $selectedOrganOrders[$organId] = $ultrasoundTypeOrgan->getSortOrder();
            }
        }

        return $selectedOrganOrders;
    }

    /**
     * @return UltrasoundType[]
     */
    private function findUltrasoundTypesForList(UltrasoundTypeRepository $ultrasoundTypeRepository): array
    {
        return $ultrasoundTypeRepository
            ->createQueryBuilder('ut')
            ->leftJoin('ut.ultrasoundTypeOrgans', 'uto')
            ->addSelect('uto')
            ->leftJoin('uto.organ', 'o')
            ->addSelect('o')
            ->orderBy('ut.sort_order', 'ASC')
            ->addOrderBy('ut.name', 'ASC')
            ->addOrderBy('uto.sortOrder', 'ASC')
            ->addOrderBy('o.sort_order', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
