<?php

namespace App\Controller;

use App\Entity\Cities;
use App\Repository\CitiesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CitiesController extends AbstractController
{
    #[Route('/cities/search', name: 'app_cities_search', methods: ['GET'])]
    public function search(Request $request, CitiesRepository $citiesRepository): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        if (mb_strlen($query) < 2) {
            return $this->json([]);
        }

        $normalizedQuery = $this->normalizeSearchText($query);
        $cities = [];
        $allCities = $citiesRepository
            ->createQueryBuilder('c')
            ->leftJoin('c.district', 'd')
            ->addSelect('d')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        foreach ($allCities as $city) {
            if (!$city instanceof Cities || $city->getName() === null) {
                continue;
            }

            if (!str_contains($this->normalizeSearchText($city->getName()), $normalizedQuery)) {
                continue;
            }

            $cities[] = $city;

            if (count($cities) >= 15) {
                break;
            }
        }

        return $this->json(array_map(
            static function (Cities $city): array {
                $district = $city->getDistrict()?->getName();
                $name = $city->getName() ?? '';

                return [
                    'id' => $city->getId(),
                    'name' => $name,
                    'district' => $district,
                    'label' => $district === null ? $name : sprintf('%s - %s', $district, $name),
                ];
            },
            $cities,
        ));
    }

    private function normalizeSearchText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'ă' => 'a',
            'â' => 'a',
            'î' => 'i',
            'ș' => 's',
            'ş' => 's',
            'ț' => 't',
            'ţ' => 't',
        ]);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
