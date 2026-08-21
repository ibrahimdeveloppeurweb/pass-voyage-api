<?php

namespace App\Manager\Business;

use App\Entity\Business\City;
use App\Entity\Business\Route;
use App\Exception\ExceptionApi;
use App\Repository\Business\CityRepository;
use App\Repository\Business\RouteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class RouteManager
{
    private $em;
    private $routeRepository;
    private $cityRepository;

    public function __construct(
        EntityManagerInterface $em,
        RouteRepository $routeRepository,
        CityRepository $cityRepository
    ) {
        $this->em = $em;
        $this->routeRepository = $routeRepository;
        $this->cityRepository = $cityRepository;
    }

    private function resolveCity($cityNameOrId): City
    {
        if (empty($cityNameOrId)) {
            throw new ExceptionApi('La ville est obligatoire.', ['msg' => 'Ville obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawName = trim((string)$cityNameOrId);
        $formattedName = mb_convert_case($rawName, MB_CASE_TITLE, "UTF-8");

        if (is_numeric($rawName)) {
            $city = $this->cityRepository->find((int)$rawName);
        } else {
            $city = $this->cityRepository->findOneBy(['name' => $formattedName])
                 ?? $this->cityRepository->findOneBy(['name' => $rawName])
                 ?? $this->cityRepository->findOneBy(['uuid' => $rawName]);

            if (!$city) {
                $allCities = $this->cityRepository->findAll();
                foreach ($allCities as $c) {
                    if (mb_strtolower(trim($c->getName())) === mb_strtolower($rawName)) {
                        $city = $c;
                        break;
                    }
                }
            }
        }

        if (!$city) {
            $city = new City();
            $city->setName($formattedName);
            $this->em->persist($city);
            $this->em->flush();
        }

        return $city;
    }

    public function create($data): Route
    {
        $dataObj = is_array($data) ? (object)$data : $data;

        $depName = trim($dataObj->departureCity ?? $dataObj->villeDepart ?? $dataObj->departure ?? '');
        $arrName = trim($dataObj->arrivalCity ?? $dataObj->villeArrivee ?? $dataObj->arrival ?? '');

        if (!$depName) {
            throw new ExceptionApi('La ville de départ est obligatoire.', ['msg' => 'Ville de départ obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$arrName) {
            throw new ExceptionApi('La ville d\'arrivée est obligatoire.', ['msg' => 'Ville d\'arrivée obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $departureCity = $this->resolveCity($depName);
        $arrivalCity = $this->resolveCity($arrName);

        $distance = trim($dataObj->distance ?? '');

        $route = new Route();
        $route->setDepartureCity($departureCity);
        $route->setArrivalCity($arrivalCity);
        if ($distance) {
            $route->setDistance($distance);
        }

        if (isset($dataObj->isActive)) {
            $route->setIsActive((bool)$dataObj->isActive);
        } elseif (isset($dataObj->statut)) {
            $route->setIsActive($dataObj->statut === 'Actif' || $dataObj->statut === true);
        } else {
            $route->setIsActive(true);
        }

        $this->em->persist($route);
        $this->em->flush();

        return $route;
    }

    public function update(string $uuid, $data): Route
    {
        $route = $this->routeRepository->findOneBy(['uuid' => $uuid]);
        if (!$route && is_numeric($uuid)) {
            $route = $this->routeRepository->find((int)$uuid);
        }

        if (!$route) {
            throw new ExceptionApi('Trajet introuvable.', ['msg' => 'Trajet introuvable'], Response::HTTP_NOT_FOUND);
        }

        $dataObj = is_array($data) ? (object)$data : $data;

        $depName = trim($dataObj->departureCity ?? $dataObj->villeDepart ?? $dataObj->departure ?? '');
        if ($depName) {
            $departureCity = $this->resolveCity($depName);
            $route->setDepartureCity($departureCity);
        }

        $arrName = trim($dataObj->arrivalCity ?? $dataObj->villeArrivee ?? $dataObj->arrival ?? '');
        if ($arrName) {
            $arrivalCity = $this->resolveCity($arrName);
            $route->setArrivalCity($arrivalCity);
        }

        if (isset($dataObj->distance)) {
            $route->setDistance(trim($dataObj->distance));
        }

        if (isset($dataObj->isActive)) {
            $route->setIsActive((bool)$dataObj->isActive);
        } elseif (isset($dataObj->statut)) {
            $route->setIsActive($dataObj->statut === 'Actif' || $dataObj->statut === true);
        }

        $this->em->persist($route);
        $this->em->flush();

        return $route;
    }

    public function delete(Route $route): void
    {
        $this->em->remove($route);
        $this->em->flush();
    }

    public function toggle(Route $route): Route
    {
        $route->setIsActive(!$route->getIsActive());
        $this->em->persist($route);
        $this->em->flush();
        return $route;
    }

    public function formatRoute(Route $route): array
    {
        $depName = $route->getDepartureCity() ? $route->getDepartureCity()->getName() : ($route->getDepartureStation() ? $route->getDepartureStation()->getName() : 'Départ');
        $arrName = $route->getArrivalCity() ? $route->getArrivalCity()->getName() : ($route->getArrivalStation() ? $route->getArrivalStation()->getName() : 'Arrivée');

        return [
            'id' => $route->getId(),
            'uuid' => $route->getUuid(),
            'villeDepart' => $depName,
            'departureCity' => $depName,
            'villeArrivee' => $arrName,
            'arrivalCity' => $arrName,
            'distance' => $route->getDistance() ?? 'N/A',
            'statut' => $route->getIsActive() ? 'Actif' : 'Inactif',
            'isActive' => $route->getIsActive(),
        ];
    }

    public function getAllRoutesFormatted(): array
    {
        $routes = $this->routeRepository->findBy([], ['id' => 'DESC']);
        return array_map(function(Route $route) {
            return $this->formatRoute($route);
        }, $routes);
    }

    public function findRoute(?string $uuid): ?Route
    {
        if (!$uuid) {
            return null;
        }
        $route = $this->routeRepository->findOneBy(['uuid' => $uuid]);
        if (!$route && is_numeric($uuid)) {
            $route = $this->routeRepository->find((int)$uuid);
        }
        return $route;
    }

    public function deleteByUuid(string $uuid): void
    {
        $route = $this->findRoute($uuid);
        if (!$route) {
            throw new ExceptionApi('Trajet introuvable.', ['msg' => 'Trajet introuvable'], Response::HTTP_NOT_FOUND);
        }
        $this->delete($route);
    }

    public function toggleByUuid(string $uuid): Route
    {
        $route = $this->findRoute($uuid);
        if (!$route) {
            throw new ExceptionApi('Trajet introuvable.', ['msg' => 'Trajet introuvable'], Response::HTTP_NOT_FOUND);
        }
        return $this->toggle($route);
    }
}
