<?php

namespace App\Command;

use App\Entity\Cities;
use App\Entity\Districts;
use App\Repository\CitiesRepository;
use App\Repository\DistrictsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:import-localities',
    description: 'Import districts and cities from the Moldova localities XLSX file.',
)]
class ImportLocalitiesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DistrictsRepository $districtsRepository,
        private readonly CitiesRepository $citiesRepository,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'file',
            InputArgument::OPTIONAL,
            'Path to XLSX file, relative to project dir or absolute.',
            'public/lista_localitati_R.Moldova.xlsx',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');
        $path = str_starts_with($file, '/') ? $file : $this->kernel->getProjectDir().'/'.$file;

        if (!is_file($path)) {
            $io->error(sprintf('File not found: %s', $path));

            return Command::FAILURE;
        }

        $rows = $this->readLocalities($path);
        $districts = $this->loadDistricts();
        $cities = $this->loadCities();

        $districtsAdded = 0;
        $citiesAdded = 0;

        foreach ($rows as [$districtName, $cityName]) {
            if (!isset($districts[$districtName])) {
                $district = (new Districts())->setName($districtName);
                $this->entityManager->persist($district);
                $districts[$districtName] = $district;
                ++$districtsAdded;
            }

            $key = $districtName.'|'.$cityName;

            if (isset($cities[$key])) {
                continue;
            }

            $city = (new Cities())
                ->setDistrict($districts[$districtName])
                ->setName($cityName)
            ;

            $this->entityManager->persist($city);
            $cities[$key] = $city;
            ++$citiesAdded;
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Import finished. Rows read: %d. Districts added: %d. Cities added: %d.',
            count($rows),
            $districtsAdded,
            $citiesAdded,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function readLocalities(string $path): array
    {
        $zip = new \ZipArchive();
        $result = $zip->open($path);

        if ($result !== true) {
            throw new \RuntimeException(sprintf('Cannot open XLSX file: %s', $path));
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet === false) {
            throw new \RuntimeException('Cannot find xl/worksheets/sheet1.xml in XLSX file.');
        }

        $xml = simplexml_load_string($sheet);

        if ($xml === false) {
            throw new \RuntimeException('Cannot parse sheet1.xml.');
        }

        $rows = [];

        foreach ($xml->sheetData->row as $row) {
            $rowNumber = (int) $row['r'];
            $cells = [];

            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $column = preg_replace('/\d+$/', '', $reference);

                if ($column === null || $column === '') {
                    continue;
                }

                $cells[$column] = $this->normalizeName($this->cellValue($cell, $sharedStrings));
            }

            if ($rowNumber === 1) {
                continue;
            }

            $districtName = $cells['A'] ?? '';
            $cityName = $cells['B'] ?? '';

            if ($districtName === '' || $cityName === '') {
                continue;
            }

            $rows[] = [$districtName, $cityName];
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(\ZipArchive $zip): array
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sharedStringsXml);

        if ($xml === false) {
            throw new \RuntimeException('Cannot parse sharedStrings.xml.');
        }

        $strings = [];

        foreach ($xml->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $value = '';

            foreach ($item->r as $run) {
                $value .= (string) $run->t;
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            $index = (int) $cell->v;

            return $sharedStrings[$index] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) $cell->is->t;
        }

        return (string) $cell->v;
    }

    private function normalizeName(string $value): string
    {
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @return array<string, Districts>
     */
    private function loadDistricts(): array
    {
        $districts = [];

        foreach ($this->districtsRepository->findAll() as $district) {
            $name = $district->getName();

            if ($name !== null) {
                $districts[$name] = $district;
            }
        }

        return $districts;
    }

    /**
     * @return array<string, Cities>
     */
    private function loadCities(): array
    {
        $cities = [];
        $items = $this->citiesRepository
            ->createQueryBuilder('c')
            ->leftJoin('c.district', 'd')
            ->addSelect('d')
            ->getQuery()
            ->getResult()
        ;

        foreach ($items as $city) {
            if (!$city instanceof Cities) {
                continue;
            }

            $district = $city->getDistrict();

            if ($district === null || $district->getName() === null || $city->getName() === null) {
                continue;
            }

            $cities[$district->getName().'|'.$city->getName()] = $city;
        }

        return $cities;
    }
}
