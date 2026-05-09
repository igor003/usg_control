<?php

namespace App\Controller;

use App\Entity\Organs;
use App\Entity\OrganParameters;
use App\Entity\Parameters;
use App\Entity\Patients;
use App\Entity\ExaminationSessionOrgans;
use App\Entity\ExaminationSessionParameterResults;
use App\Entity\ExaminationSessions;
use App\Repository\ExaminationSessionsRepository;
use App\Repository\OrgansRepository;
use App\Repository\ParametersRepository;
use App\Repository\PatientsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExaminationSessionController extends AbstractController
{
    #[Route('/sessions', name: 'app_sessions_index', methods: ['GET'])]
    public function index(Request $request, ExaminationSessionsRepository $examinationSessionsRepository): Response
    {
        $sessions = $examinationSessionsRepository
            ->createQueryBuilder('s')
            ->leftJoin('s.patient', 'p')
            ->addSelect('p')
            ->leftJoin('p.city', 'c')
            ->addSelect('c')
            ->leftJoin('c.district', 'd')
            ->addSelect('d')
            ->leftJoin('s.session_organs', 'so')
            ->addSelect('so')
            ->leftJoin('so.parameter_results', 'pr')
            ->addSelect('pr')
            ->orderBy('s.session_date', 'DESC')
            ->addOrderBy('so.sort_order', 'ASC')
            ->addOrderBy('pr.sort_order', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $this->render('sessions/index.html.twig', [
            'sessions' => $sessions,
            'createdSessionId' => $request->query->getInt('created') ?: null,
            'printSessionId' => $request->query->getInt('print') ?: null,
        ]);
    }

    #[Route('/sessions/{id}', name: 'app_sessions_show', methods: ['GET'])]
    public function show(ExaminationSessions $session, ExaminationSessionsRepository $examinationSessionsRepository): Response
    {
        $session = $this->findSessionWithResults($examinationSessionsRepository, (int) $session->getId());

        if (!$session instanceof ExaminationSessions) {
            throw $this->createNotFoundException('Sesiunea nu a fost găsită.');
        }

        return $this->render('sessions/show.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/raport', name: 'app_report_index', methods: ['GET'])]
    public function report(ExaminationSessionsRepository $examinationSessionsRepository): Response
    {
        return $this->render('reports/index.html.twig', [
            'sessions' => $this->findReportSessions($examinationSessionsRepository),
        ]);
    }

    #[Route('/raport/export', name: 'app_report_export', methods: ['GET'])]
    public function exportReport(Request $request, ExaminationSessionsRepository $examinationSessionsRepository): Response
    {
        $rows = $this->filterReportRows(
            $this->buildReportRows($this->findReportSessions($examinationSessionsRepository)),
            $request,
        );
        $filename = sprintf('raport_examinari_%s.xlsx', (new \DateTimeImmutable())->format('Y-m-d_H-i'));
        $headers = [
            'Data examinării',
            'Nume / Prenume pacient',
            'Tipul examinării',
            'Înlesniri',
            'Raion / Municipiu',
            'Localitatea',
            'Adresa',
            'Nr. telefon',
        ];

        $response = new Response($this->buildXlsxContent($headers, array_map(
            static fn (array $row): array => $row['export'],
            $rows,
        )));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-store, max-age=0');

        return $response;
    }

    #[Route('/session/start', name: 'app_session_start', methods: ['GET'])]
    public function start(OrgansRepository $organsRepository): Response
    {
        $organs = array_map(
            fn (Organs $organ): array => [
                'id' => $organ->getId(),
                'name' => $organ->getName() ?? '',
                'paired' => $organ->isParied(),
                'imagePath' => $this->resolveOrganImagePath($organ),
                'parameters' => array_map(
                    static fn (OrganParameters $organParameter): array => [
                        'id' => $organParameter->getParameter()?->getId(),
                        'name' => $organParameter->getParameter()?->getName() ?? '',
                        'valueType' => $organParameter->getParameter()?->getValueType() ?? 'text',
                        'valueContent' => array_values($organParameter->getParameter()?->getValueContent() ?? []),
                        'sortOrder' => $organParameter->getSortOrder(),
                    ],
                    array_values(array_filter(
                        $organ->getOrganParameters()->toArray(),
                        static fn (OrganParameters $organParameter): bool => $organParameter->getParameter() instanceof Parameters,
                    )),
                ),
            ],
            $organsRepository
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
                ->getResult(),
        );

        return $this->render('session/start.html.twig', [
            'organs' => $organs,
        ]);
    }

    #[Route('/session/save', name: 'app_session_save', methods: ['POST'])]
    public function save(
        Request $request,
        PatientsRepository $patientsRepository,
        OrgansRepository $organsRepository,
        ParametersRepository $parametersRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Datele formularului nu sunt valide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $isIncognito = (bool) ($data['isIncognito'] ?? false);
        $patient = null;

        if (!$isIncognito) {
            $patient = $patientsRepository->find((int) ($data['patientId'] ?? 0));

            if (!$patient instanceof Patients) {
                return $this->json([
                    'success' => false,
                    'message' => 'Pacientul nu a fost găsit.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $organsData = $data['organs'] ?? [];

        if (!is_array($organsData) || $organsData === []) {
            return $this->json([
                'success' => false,
                'message' => 'Selectați cel puțin un organ.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $organsById = $this->mapById($organsRepository->findBy([
            'id' => array_values(array_unique(array_map(
                static fn (array $organ): int => (int) ($organ['organId'] ?? 0),
                array_filter($organsData, 'is_array')
            ))),
        ]));
        $parametersById = $this->mapById($parametersRepository->findBy([
            'id' => $this->collectParameterIds($organsData),
        ]));
        $now = new \DateTimeImmutable();
        $session = (new ExaminationSessions())
            ->setPatient($patient ?: null)
            ->setSessionDate($now)
            ->setSessionNote($this->normalizeNullableString($data['sessionNote'] ?? null))
            ->setSessionConclusion($this->normalizeNullableString($data['sessionConclusion'] ?? null))
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
        ;

        foreach ($organsData as $organIndex => $organData) {
            if (!is_array($organData)) {
                continue;
            }

            $organId = (int) ($organData['organId'] ?? 0);
            $organ = $organsById[$organId] ?? null;

            if (!$organ instanceof Organs) {
                return $this->json([
                    'success' => false,
                    'message' => 'Unul dintre organele selectate nu a fost găsit.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $side = (string) ($organData['side'] ?? 'single');

            if (!in_array($side, ['single', 'right', 'left'], true)) {
                $side = 'single';
            }

            $sessionOrgan = (new ExaminationSessionOrgans())
                ->setOrgan($organ)
                ->setOrganName($organ->getName() ?? '')
                ->setOrganImagePath($organ->getImagePath())
                ->setSide($side)
                ->setOrganNote($this->normalizeNullableString($organData['note'] ?? null))
                ->setSortOrder((int) ($organData['sortOrder'] ?? $organIndex + 1))
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
            ;

            $parametersData = $organData['parameters'] ?? [];

            if (is_array($parametersData)) {
                foreach ($parametersData as $parameterIndex => $parameterData) {
                    if (!is_array($parameterData)) {
                        continue;
                    }

                    $parameterId = (int) ($parameterData['parameterId'] ?? 0);
                    $parameter = $parametersById[$parameterId] ?? null;

                    if (!$parameter instanceof Parameters) {
                        return $this->json([
                            'success' => false,
                            'message' => 'Unul dintre parametrii selectați nu a fost găsit.',
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $result = (new ExaminationSessionParameterResults())
                        ->setParameter($parameter)
                        ->setParameterName($parameter->getName() ?? '')
                        ->setParameterValueType($parameter->getValueType() ?? 'text')
                        ->setParameterValueContent(array_values($parameter->getValueContent()))
                        ->setValue($this->normalizeNullableString($parameterData['value'] ?? null))
                        ->setSortOrder((int) ($parameterData['sortOrder'] ?? $parameterIndex + 1))
                        ->setCreatedAt($now)
                        ->setUpdatedAt($now)
                    ;

                    $sessionOrgan->addParameterResult($result);
                }
            }

            $session->addSessionOrgan($sessionOrgan);
        }

        $entityManager->persist($session);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Formularul a fost salvat cu succes.',
            'sessionId' => $session->getId(),
            'printUrl' => $this->generateUrl('app_session_print', [
                'id' => $session->getId(),
                'auto_print' => 1,
            ]),
            'redirectUrl' => $this->generateUrl('app_sessions_index', [
                'created' => $session->getId(),
                'print' => $session->getId(),
            ]),
        ]);
    }

    /**
     * @return ExaminationSessions[]
     */
    private function findReportSessions(ExaminationSessionsRepository $examinationSessionsRepository): array
    {
        return $examinationSessionsRepository
            ->createQueryBuilder('s')
            ->leftJoin('s.patient', 'p')
            ->addSelect('p')
            ->leftJoin('p.city', 'c')
            ->addSelect('c')
            ->leftJoin('c.district', 'd')
            ->addSelect('d')
            ->leftJoin('s.session_organs', 'so')
            ->addSelect('so')
            ->orderBy('s.session_date', 'DESC')
            ->addOrderBy('p.last_name', 'ASC')
            ->addOrderBy('p.first_name', 'ASC')
            ->addOrderBy('so.sort_order', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param ExaminationSessions[] $sessions
     * @return array<int, array{date: string, filters: string[], search: string, export: string[]}>
     */
    private function buildReportRows(array $sessions): array
    {
        return array_map(
            fn (ExaminationSessions $session): array => $this->buildReportRow($session),
            $sessions,
        );
    }

    /**
     * @return array{date: string, filters: string[], search: string, export: string[]}
     */
    private function buildReportRow(ExaminationSessions $session): array
    {
        $patient = $session->getPatient();
        $city = $patient?->getCity();
        $district = $city?->getDistrict();
        $examinationTypes = [];

        foreach ($session->getSessionOrgans() as $sessionOrgan) {
            if (!$sessionOrgan instanceof ExaminationSessionOrgans) {
                continue;
            }

            $sideLabel = match ($sessionOrgan->getSide()) {
                'right' => 'Dreapta',
                'left' => 'Stânga',
                default => '',
            };
            $organName = (string) $sessionOrgan->getOrganName();
            $examinationTypes[] = $organName . ($sideLabel !== '' ? ' - ' . $sideLabel : '');
        }

        $sessionDate = $session->getSessionDate();
        $filters = [
            $sessionDate?->format('d.m.Y H:i') ?? '',
            $patient ? trim((string) $patient->getLastName() . ' ' . (string) $patient->getFirstName()) : '',
            implode(' ', $examinationTypes),
            $patient && $patient->isBeneficiary() ? 'Da' : 'Nu',
            $district?->getName() ?? '',
            $city?->getName() ?? '',
            $patient?->getAddress() ?? '',
            $patient?->getPhone() ?? '',
        ];

        return [
            'date' => $sessionDate?->format('Y-m-d') ?? '',
            'filters' => $filters,
            'search' => implode(' ', $filters),
            'export' => [
                $filters[0],
                $filters[1],
                implode(', ', $examinationTypes),
                $filters[3],
                $filters[4],
                $filters[5],
                $filters[6],
                $filters[7],
            ],
        ];
    }

    /**
     * @param array<int, array{date: string, filters: string[], search: string, export: string[]}> $rows
     * @return array<int, array{date: string, filters: string[], search: string, export: string[]}>
     */
    private function filterReportRows(array $rows, Request $request): array
    {
        $globalTerm = $this->normalizeSearchText((string) $request->query->get('global', ''));
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));
        $columnFilters = [];

        for ($column = 0; $column < 8; $column++) {
            $columnFilters[$column] = $this->normalizeSearchText((string) $request->query->get('column_' . $column, ''));
        }

        return array_values(array_filter($rows, function (array $row) use ($globalTerm, $dateFrom, $dateTo, $columnFilters): bool {
            if ($globalTerm !== '' && !str_contains($this->normalizeSearchText($row['search']), $globalTerm)) {
                return false;
            }

            if ($dateFrom !== '' && ($row['date'] === '' || $row['date'] < $dateFrom)) {
                return false;
            }

            if ($dateTo !== '' && ($row['date'] === '' || $row['date'] > $dateTo)) {
                return false;
            }

            foreach ($columnFilters as $column => $term) {
                if ($term === '') {
                    continue;
                }

                if (!str_contains($this->normalizeSearchText($row['filters'][$column] ?? ''), $term)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @param string[] $headers
     * @param array<int, string[]> $rows
     */
    private function buildXlsxContent(array $headers, array $rows): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'report_xlsx_');

        if ($temporaryFile === false) {
            throw new \RuntimeException('Fișierul temporar pentru export nu a putut fi creat.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($temporaryFile, \ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryFile);

            throw new \RuntimeException('Arhiva XLSX nu a putut fi creată.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Raport" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '</styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->buildWorksheetXml($headers, $rows));
        $zip->close();

        $content = file_get_contents($temporaryFile);
        @unlink($temporaryFile);

        if ($content === false) {
            throw new \RuntimeException('Fișierul XLSX nu a putut fi citit.');
        }

        return $content;
    }

    /**
     * @param string[] $headers
     * @param array<int, string[]> $rows
     */
    private function buildWorksheetXml(array $headers, array $rows): string
    {
        $sheetRows = ['<row r="1">'];

        foreach ($headers as $columnIndex => $header) {
            $sheetRows[] = $this->buildXlsxCell($columnIndex, 1, $header, 1);
        }

        $sheetRows[] = '</row>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;
            $sheetRows[] = sprintf('<row r="%d">', $rowNumber);

            foreach ($row as $columnIndex => $value) {
                $sheetRows[] = $this->buildXlsxCell($columnIndex, $rowNumber, (string) $value);
            }

            $sheetRows[] = '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<cols><col min="1" max="1" width="18" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/><col min="3" max="3" width="36" customWidth="1"/><col min="4" max="4" width="12" customWidth="1"/><col min="5" max="8" width="24" customWidth="1"/></cols>'
            . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '</worksheet>';
    }

    private function buildXlsxCell(int $columnIndex, int $rowNumber, string $value, int $styleId = 0): string
    {
        $style = $styleId > 0 ? ' s="' . $styleId . '"' : '';

        return sprintf(
            '<c r="%s%d" t="inlineStr"%s><is><t xml:space="preserve">%s</t></is></c>',
            $this->getExcelColumnName($columnIndex),
            $rowNumber,
            $style,
            htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
        );
    }

    private function getExcelColumnName(int $columnIndex): string
    {
        $name = '';
        $columnIndex += 1;

        while ($columnIndex > 0) {
            $remainder = ($columnIndex - 1) % 26;
            $name = chr(65 + $remainder) . $name;
            $columnIndex = intdiv($columnIndex - $remainder, 26);
        }

        return $name;
    }

    #[Route('/session/{id}/print', name: 'app_session_print', methods: ['GET'])]
    public function print(
        ExaminationSessions $session,
        ExaminationSessionsRepository $examinationSessionsRepository,
    ): Response {
        $session = $this->findSessionWithResults($examinationSessionsRepository, (int) $session->getId());

        if (!$session instanceof ExaminationSessions) {
            throw $this->createNotFoundException('Sesiunea nu a fost găsită.');
        }

        return $this->render('session/print.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/session/patient-by-idnp', name: 'app_session_patient_by_idnp', methods: ['GET'])]
    public function patientByIdnp(Request $request, PatientsRepository $patientsRepository): JsonResponse
    {
        $idnp = trim((string) $request->query->get('idnp', ''));

        if (!preg_match('/^\d{13}$/', $idnp)) {
            return $this->json([
                'valid' => false,
                'found' => false,
                'message' => 'IDNP trebuie să conțină exact 13 cifre.',
            ]);
        }

        $patient = $patientsRepository
            ->createQueryBuilder('p')
            ->leftJoin('p.city', 'c')
            ->addSelect('c')
            ->leftJoin('c.district', 'd')
            ->addSelect('d')
            ->andWhere('p.idnp = :idnp')
            ->setParameter('idnp', $idnp)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$patient instanceof Patients) {
            return $this->json([
                'valid' => true,
                'found' => false,
                'message' => 'Pacientul cu acest IDNP nu a fost găsit.',
            ]);
        }

        return $this->json([
            'valid' => true,
            'found' => true,
            'message' => 'Pacient găsit.',
            'patient' => $this->patientToArray($patient),
        ]);
    }

    #[Route('/session/patients-search', name: 'app_session_patients_search', methods: ['GET'])]
    public function patientsSearch(Request $request, PatientsRepository $patientsRepository): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        if (mb_strlen($query) < 2) {
            return $this->json([
                'patients' => [],
            ]);
        }

        $terms = array_values(array_filter(explode(' ', $this->normalizeSearchText($query))));
        $matches = [];
        $patients = $patientsRepository
            ->createQueryBuilder('p')
            ->leftJoin('p.city', 'c')
            ->addSelect('c')
            ->leftJoin('c.district', 'd')
            ->addSelect('d')
            ->orderBy('p.last_name', 'ASC')
            ->addOrderBy('p.first_name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        foreach ($patients as $patient) {
            if (!$patient instanceof Patients) {
                continue;
            }

            $searchText = $this->normalizeSearchText(sprintf(
                '%s %s %s %s %s',
                $patient->getLastName() ?? '',
                $patient->getFirstName() ?? '',
                $patient->getBirthYear() === null ? '' : (string) $patient->getBirthYear(),
                $patient->getIdnp() ?? '',
                $patient->getPhone() ?? '',
                $patient->getSeria() ?? '',
            ));
            $isMatch = true;

            foreach ($terms as $term) {
                if (!str_contains($searchText, $term)) {
                    $isMatch = false;
                    break;
                }
            }

            if (!$isMatch) {
                continue;
            }

            $matches[] = $this->patientToArray($patient);

            if (count($matches) >= 12) {
                break;
            }
        }

        return $this->json([
            'patients' => $matches,
        ]);
    }

    private function resolveOrganImagePath(Organs $organ): ?string
    {
        $imagePath = trim((string) $organ->getImagePath());

        if ($imagePath !== '') {
            return '/' . ltrim($imagePath, '/');
        }

        $name = $organ->getName();

        if ($name === null || $name === '') {
            return null;
        }

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $candidates = array_unique([
            'uploads/organs/' . $name . '.png',
            'uploads/organs/' . mb_strtolower($name) . '.png',
            'uploads/organs/' . $this->normalizeOrganImageName($name) . '.png',
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($projectDir . '/public/' . $candidate)) {
                return '/' . $candidate;
            }
        }

        return null;
    }

    private function normalizeOrganImageName(string $value): string
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

    private function formatGender(?string $gender): string
    {
        return match ($gender) {
            'female' => 'Feminin',
            'male' => 'Masculin',
            default => 'Altul',
        };
    }

    /**
     * @return array<string, int|string|null>
     */
    private function patientToArray(Patients $patient): array
    {
        $city = $patient->getCity();
        $district = $city?->getDistrict();

        return [
            'id' => $patient->getId(),
            'firstName' => $patient->getFirstName(),
            'lastName' => $patient->getLastName(),
            'gender' => $this->formatGender($patient->getGender()),
            'birthYear' => $patient->getBirthYear(),
            'phone' => $patient->getPhone(),
            'idnp' => $patient->getIdnp(),
            'seria' => $patient->getSeria(),
            'district' => $district?->getName(),
            'city' => $city?->getName(),
            'address' => $patient->getAddress(),
            'beneficiary' => $patient->isBeneficiary() ? 'Da' : 'Nu',
        ];
    }

    /**
     * @param object[] $entities
     * @return array<int, object>
     */
    private function mapById(array $entities): array
    {
        $mapped = [];

        foreach ($entities as $entity) {
            if (!method_exists($entity, 'getId')) {
                continue;
            }

            $id = $entity->getId();

            if ($id !== null) {
                $mapped[$id] = $entity;
            }
        }

        return $mapped;
    }

    /**
     * @param mixed[] $organsData
     * @return int[]
     */
    private function collectParameterIds(array $organsData): array
    {
        $parameterIds = [];

        foreach ($organsData as $organData) {
            if (!is_array($organData) || !is_array($organData['parameters'] ?? null)) {
                continue;
            }

            foreach ($organData['parameters'] as $parameterData) {
                if (is_array($parameterData)) {
                    $parameterIds[] = (int) ($parameterData['parameterId'] ?? 0);
                }
            }
        }

        return array_values(array_unique(array_filter(
            $parameterIds,
            static fn (int $parameterId): bool => $parameterId > 0
        )));
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function findSessionWithResults(
        ExaminationSessionsRepository $examinationSessionsRepository,
        int $id,
    ): ?ExaminationSessions {
        return $examinationSessionsRepository
            ->createQueryBuilder('s')
            ->leftJoin('s.patient', 'p')
            ->addSelect('p')
            ->leftJoin('p.city', 'c')
            ->addSelect('c')
            ->leftJoin('c.district', 'd')
            ->addSelect('d')
            ->leftJoin('s.session_organs', 'so')
            ->addSelect('so')
            ->leftJoin('so.organ', 'o')
            ->addSelect('o')
            ->leftJoin('o.ultrasound_type', 'ut')
            ->addSelect('ut')
            ->leftJoin('so.parameter_results', 'pr')
            ->addSelect('pr')
            ->andWhere('s.id = :id')
            ->setParameter('id', $id)
            ->addOrderBy('so.sort_order', 'ASC')
            ->addOrderBy('pr.sort_order', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
