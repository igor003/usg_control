<?php

namespace App\Controller;

use App\Entity\OrganParameters;
use App\Entity\Organs;
use App\Entity\Parameters;
use App\Form\OrganControlsType;
use App\Repository\OrgansRepository;
use App\Repository\ParametersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrganControlsController extends AbstractController
{
    #[Route('/organ-controls/new', name: 'app_organ_controls_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        OrgansRepository $organsRepository,
        ParametersRepository $parametersRepository,
    ): Response {
        $form = $this->createForm(OrganControlsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $organ = $form->get('organ')->getData();

            if ($organ instanceof Organs) {
                $this->syncParameters($organ, $this->readSelectedParameters($request, $parametersRepository), $entityManager);
                $entityManager->flush();

                $this->addFlash('success', 'Controalele organului au fost salvate cu succes.');
            }

            return $this->redirectToRoute('app_organ_controls_new');
        }

        return $this->render('organ_controls/new.html.twig', [
            'form' => $form,
            'organs' => $this->findOrgansWithParameters($organsRepository),
            'parameters' => $parametersRepository->findBy([], ['name' => 'ASC']),
            'selected_parameter_orders' => [],
            'page_title' => 'Controale pentru organ',
            'page_description' => 'Asociați organele cu parametrii de control utilizați în examinare.',
            'editing_organ_id' => null,
        ]);
    }

    #[Route('/organ-controls/{id}/edit', name: 'app_organ_controls_edit', methods: ['GET', 'POST'])]
    public function edit(
        Organs $organ,
        Request $request,
        EntityManagerInterface $entityManager,
        OrgansRepository $organsRepository,
        ParametersRepository $parametersRepository,
    ): Response {
        $form = $this->createForm(OrganControlsType::class, [
            'organ' => $organ,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedOrgan = $form->get('organ')->getData();

            if ($selectedOrgan instanceof Organs) {
                $this->syncParameters($selectedOrgan, $this->readSelectedParameters($request, $parametersRepository), $entityManager);
                $entityManager->flush();

                $this->addFlash('success', 'Controalele organului au fost actualizate cu succes.');
            }

            return $this->redirectToRoute('app_organ_controls_new');
        }

        return $this->render('organ_controls/new.html.twig', [
            'form' => $form,
            'organs' => $this->findOrgansWithParameters($organsRepository),
            'parameters' => $parametersRepository->findBy([], ['name' => 'ASC']),
            'selected_parameter_orders' => $this->getSelectedParameterOrders($organ),
            'page_title' => 'Editare controale organ',
            'page_description' => 'Actualizați controalele asociate organului selectat.',
            'editing_organ_id' => $organ->getId(),
        ]);
    }

    /**
     * @param array<int, array{parameter: Parameters, sortOrder: int}> $selectedParameters
     */
    private function syncParameters(Organs $organ, array $selectedParameters, EntityManagerInterface $entityManager): void
    {
        $existingByParameterId = [];

        foreach ($organ->getOrganParameters() as $organParameter) {
            $parameterId = $organParameter->getParameter()?->getId();

            if ($parameterId !== null) {
                $existingByParameterId[$parameterId] = $organParameter;
            }
        }

        $selectedIds = [];

        foreach ($selectedParameters as $selectedParameter) {
            $parameter = $selectedParameter['parameter'];
            $parameterId = $parameter->getId();

            if ($parameterId === null) {
                continue;
            }

            $selectedIds[] = $parameterId;
            $organParameter = $existingByParameterId[$parameterId] ?? null;

            if (!$organParameter instanceof OrganParameters) {
                $organParameter = (new OrganParameters())
                    ->setOrgan($organ)
                    ->setParameter($parameter)
                ;

                $organ->addOrganParameter($organParameter);
                $entityManager->persist($organParameter);
            }

            $organParameter->setSortOrder($selectedParameter['sortOrder']);
        }

        foreach ($existingByParameterId as $parameterId => $organParameter) {
            if (!in_array($parameterId, $selectedIds, true)) {
                $organ->removeOrganParameter($organParameter);
                $entityManager->remove($organParameter);
            }
        }
    }

    /**
     * @return array<int, array{parameter: Parameters, sortOrder: int}>
     */
    private function readSelectedParameters(Request $request, ParametersRepository $parametersRepository): array
    {
        $parameterIds = array_values(array_unique(array_filter(
            array_map('intval', $request->request->all('parameter_ids')),
            static fn (int $parameterId): bool => $parameterId > 0,
        )));

        if ($parameterIds === []) {
            return [];
        }

        $orders = $request->request->all('parameter_order');
        $parameters = $parametersRepository->findBy(['id' => $parameterIds]);
        $selectedParameters = [];

        foreach ($parameters as $parameter) {
            $parameterId = $parameter->getId();

            if ($parameterId === null) {
                continue;
            }

            $selectedParameters[] = [
                'parameter' => $parameter,
                'sortOrder' => max(0, (int) ($orders[$parameterId] ?? 0)),
            ];
        }

        usort(
            $selectedParameters,
            static fn (array $left, array $right): int => $left['sortOrder'] <=> $right['sortOrder']
        );

        return $selectedParameters;
    }

    /**
     * @return array<int, int>
     */
    private function getSelectedParameterOrders(Organs $organ): array
    {
        $selectedParameterOrders = [];

        foreach ($organ->getOrganParameters() as $organParameter) {
            $parameterId = $organParameter->getParameter()?->getId();

            if ($parameterId !== null) {
                $selectedParameterOrders[$parameterId] = $organParameter->getSortOrder();
            }
        }

        return $selectedParameterOrders;
    }

    /**
     * @return Organs[]
     */
    private function findOrgansWithParameters(OrgansRepository $organsRepository): array
    {
        return $organsRepository
            ->createQueryBuilder('o')
            ->leftJoin('o.organParameters', 'op')
            ->addSelect('op')
            ->leftJoin('op.parameter', 'p')
            ->addSelect('p')
            ->orderBy('o.sort_order', 'ASC')
            ->addOrderBy('o.name', 'ASC')
            ->addOrderBy('op.sortOrder', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
