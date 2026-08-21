<?php

namespace App\Controller;

use App\Entity\Business\Company;
use App\Entity\Business\Route as TravelRoute;
use App\Entity\Business\Tariff;
use App\Entity\Business\City;
use App\Helpers\JsonHelper;
use App\Repository\Business\CompanyRepository;
use App\Repository\Business\RouteRepository;
use App\Repository\Business\TariffRepository;
use App\Repository\Business\CityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/public/referentiel")
 */
#[Route(path: '/api/public/referentiel')]
class PublicReferentialController extends AbstractController
{
    private $companyRepository;
    private $routeRepository;
    private $tariffRepository;
    private $cityRepository;

    public function __construct(
        CompanyRepository $companyRepository,
        RouteRepository $routeRepository,
        TariffRepository $tariffRepository,
        CityRepository $cityRepository
    ) {
        $this->companyRepository = $companyRepository;
        $this->routeRepository = $routeRepository;
        $this->tariffRepository = $tariffRepository;
        $this->cityRepository = $cityRepository;
    }

    /**
     * @Route("/demande-credit-config", name="public_referentiel_credit_config", methods={"GET"})
     * @Route("/config", name="public_referentiel_config", methods={"GET"})
     * @Route("/partners", name="public_referentiel_partners", methods={"GET"})
     */
    #[Route('/demande-credit-config', name: 'public_referentiel_credit_config', methods: ['GET'])]
    #[Route('/config', name: 'public_referentiel_config', methods: ['GET'])]
    #[Route('/partners', name: 'public_referentiel_partners', methods: ['GET'])]
    public function getDemandeCreditConfig(): JsonResponse
    {
        // 1. Compagnies actives
        $activeCompanies = $this->companyRepository->findBy(['isActive' => true], ['name' => 'ASC']);
        if (empty($activeCompanies)) {
            $activeCompanies = $this->companyRepository->findAll();
        }

        $companiesData = array_map(function(Company $c) {
            return [
                'id' => $c->getId(),
                'uuid' => $c->getUuid(),
                'name' => $c->getName(),
                'nom' => $c->getName(),
                'status' => $c->getStatus(),
                'logo' => $c->getLogo(),
            ];
        }, $activeCompanies);

        // 2. Trajets actifs
        $activeRoutes = $this->routeRepository->findBy(['isActive' => true], ['id' => 'DESC']);
        if (empty($activeRoutes)) {
            $activeRoutes = $this->routeRepository->findAll();
        }

        $routesData = array_map(function(TravelRoute $r) {
            $depName = $r->getDepartureCity() ? $r->getDepartureCity()->getName() : ($r->getDepartureStation() ? $r->getDepartureStation()->getName() : 'Départ');
            $arrName = $r->getArrivalCity() ? $r->getArrivalCity()->getName() : ($r->getArrivalStation() ? $r->getArrivalStation()->getName() : 'Arrivée');

            return [
                'id' => $r->getId(),
                'uuid' => $r->getUuid(),
                'villeDepart' => $depName,
                'departureCity' => $depName,
                'villeArrivee' => $arrName,
                'arrivalCity' => $arrName,
                'distance' => $r->getDistance(),
            ];
        }, $activeRoutes);

        // 3. Villes (collecter toutes les villes uniques)
        $cityNamesMap = [];
        foreach ($routesData as $r) {
            if (!empty($r['villeDepart'])) {
                $cityNamesMap[trim($r['villeDepart'])] = true;
            }
            if (!empty($r['villeArrivee'])) {
                $cityNamesMap[trim($r['villeArrivee'])] = true;
            }
        }

        // Ajouter les villes de la base
        $dbCities = $this->cityRepository->findAll();
        foreach ($dbCities as $c) {
            if ($c->getName()) {
                $cityNamesMap[trim($c->getName())] = true;
            }
        }

        // Villes par défaut d'Ivory Coast au cas où
        $defaultCities = ['Abidjan', 'Bouaké', 'Yamoussoukro', 'Korhogo', 'San-Pédro', 'Daloa', 'Man', 'Odienné'];
        foreach ($defaultCities as $dCity) {
            $cityNamesMap[$dCity] = true;
        }

        $citiesList = array_keys($cityNamesMap);
        sort($citiesList);

        // 4. Tarifs actifs
        $activeTariffs = $this->tariffRepository->findBy(['isActive' => true], ['id' => 'DESC']);
        if (empty($activeTariffs)) {
            $activeTariffs = $this->tariffRepository->findAll();
        }

        $tariffsData = array_map(function(Tariff $t) {
            $route = $t->getRoute();
            $depName = $route ? ($route->getDepartureCity() ? $route->getDepartureCity()->getName() : ($route->getDepartureStation() ? $route->getDepartureStation()->getName() : '')) : '';
            $arrName = $route ? ($route->getArrivalCity() ? $route->getArrivalCity()->getName() : ($route->getArrivalStation() ? $route->getArrivalStation()->getName() : '')) : '';
            $companyName = $t->getCompany() ? $t->getCompany()->getName() : 'Toutes';

            return [
                'id' => $t->getId(),
                'uuid' => $t->getUuid(),
                'routeUuid' => $route ? $route->getUuid() : null,
                'companyUuid' => $t->getCompany() ? $t->getCompany()->getUuid() : null,
                'villeDepart' => $depName,
                'departureCity' => $depName,
                'villeArrivee' => $arrName,
                'arrivalCity' => $arrName,
                'compagnie' => $companyName,
                'companyName' => $companyName,
                'price' => $t->getPrice(),
                'prix' => $t->getPrice(),
            ];
        }, $activeTariffs);

        $payload = [
            'companies' => $companiesData,
            'cities' => $citiesList,
            'routes' => $routesData,
            'tariffs' => $tariffsData,
        ];

        $response = (new JsonHelper($payload, null, 'success', 200, []))->serialize();
        return $this->json($response, 200);
    }
}
