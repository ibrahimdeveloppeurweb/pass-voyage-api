<?php

namespace App\Manager\Business;

use App\Entity\Business\City;
use App\Entity\Business\Station;
use App\Entity\Business\Company;
use App\Exception\ExceptionApi;
use App\Repository\Business\CityRepository;
use App\Repository\Business\CompanyRepository;
use App\Repository\Business\StationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class StationManager
{
    private $em;
    private $stationRepository;
    private $cityRepository;
    private $companyRepository;

    public function __construct(
        EntityManagerInterface $em,
        StationRepository $stationRepository,
        CityRepository $cityRepository,
        CompanyRepository $companyRepository
    ) {
        $this->em = $em;
        $this->stationRepository = $stationRepository;
        $this->cityRepository = $cityRepository;
        $this->companyRepository = $companyRepository;
    }

    private function resolveCity($cityNameOrId): City
    {
        if (empty($cityNameOrId)) {
            throw new ExceptionApi('La ville est obligatoire.', ['msg' => 'Ville obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (is_numeric($cityNameOrId)) {
            $city = $this->cityRepository->find((int)$cityNameOrId);
        } else {
            $city = $this->cityRepository->findOneBy(['name' => trim($cityNameOrId)])
                 ?? $this->cityRepository->findOneBy(['uuid' => trim($cityNameOrId)]);
        }

        if (!$city) {
            $city = new City();
            $city->setName(trim($cityNameOrId));
            $this->em->persist($city);
            $this->em->flush();
        }

        return $city;
    }

    private function resolveCompany($companyNameOrId): ?Company
    {
        if (empty($companyNameOrId) || $companyNameOrId === 'Toutes' || $companyNameOrId === 'Toutes les compagnies') {
            return null;
        }

        if (is_numeric($companyNameOrId)) {
            return $this->companyRepository->find((int)$companyNameOrId);
        }

        return $this->companyRepository->findOneBy(['name' => trim($companyNameOrId)])
            ?? $this->companyRepository->findOneBy(['uuid' => trim($companyNameOrId)]);
    }

    public function create($data): Station
    {
        $dataObj = is_array($data) ? (object)$data : $data;

        $name = trim($dataObj->name ?? $dataObj->nom ?? '');
        if (!$name) {
            throw new ExceptionApi('Le nom de la gare est obligatoire.', ['msg' => 'Nom obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cityName = $dataObj->city ?? $dataObj->ville ?? $dataObj->cityName ?? '';
        $city = $this->resolveCity($cityName);

        $companyInput = $dataObj->company ?? $dataObj->compagnie ?? null;
        $company = $this->resolveCompany($companyInput);

        $station = new Station();
        $station->setName($name);
        $station->setCity($city);
        $station->setCompany($company);

        if (isset($dataObj->isActive)) {
            $station->setIsActive((bool)$dataObj->isActive);
        } elseif (isset($dataObj->statut)) {
            $station->setIsActive($dataObj->statut === 'Actif' || $dataObj->statut === true);
        } else {
            $station->setIsActive(true);
        }

        $this->em->persist($station);
        $this->em->flush();

        return $station;
    }

    public function update(string $uuid, $data): Station
    {
        $station = $this->stationRepository->findOneBy(['uuid' => $uuid]);
        if (!$station && is_numeric($uuid)) {
            $station = $this->stationRepository->find((int)$uuid);
        }

        if (!$station) {
            throw new ExceptionApi('Gare introuvable.', ['msg' => 'Gare introuvable'], Response::HTTP_NOT_FOUND);
        }

        $dataObj = is_array($data) ? (object)$data : $data;

        $name = trim($dataObj->name ?? $dataObj->nom ?? '');
        if ($name) {
            $station->setName($name);
        }

        $cityName = $dataObj->city ?? $dataObj->ville ?? $dataObj->cityName ?? null;
        if (!empty($cityName)) {
            $city = $this->resolveCity($cityName);
            $station->setCity($city);
        }

        if (isset($dataObj->company) || isset($dataObj->compagnie)) {
            $companyInput = $dataObj->company ?? $dataObj->compagnie ?? null;
            $company = $this->resolveCompany($companyInput);
            $station->setCompany($company);
        }

        if (isset($dataObj->isActive)) {
            $station->setIsActive((bool)$dataObj->isActive);
        } elseif (isset($dataObj->statut)) {
            $station->setIsActive($dataObj->statut === 'Actif' || $dataObj->statut === true);
        }

        $this->em->persist($station);
        $this->em->flush();

        return $station;
    }

    public function delete(Station $station): void
    {
        $this->em->remove($station);
        $this->em->flush();
    }

    public function toggle(Station $station): Station
    {
        $station->setIsActive(!$station->getIsActive());
        $this->em->persist($station);
        $this->em->flush();
        return $station;
    }

    public function formatStation(Station $station): array
    {
        return [
            'id' => $station->getId(),
            'uuid' => $station->getUuid(),
            'nom' => $station->getName(),
            'name' => $station->getName(),
            'ville' => $station->getCity() ? $station->getCity()->getName() : 'N/A',
            'city' => $station->getCity() ? [
                'id' => $station->getCity()->getId(),
                'uuid' => $station->getCity()->getUuid(),
                'name' => $station->getCity()->getName(),
            ] : null,
            'compagnie' => $station->getCompany() ? $station->getCompany()->getName() : 'Toutes les compagnies',
            'company' => $station->getCompany() ? [
                'id' => $station->getCompany()->getId(),
                'uuid' => $station->getCompany()->getUuid(),
                'name' => $station->getCompany()->getName(),
            ] : null,
            'statut' => $station->getIsActive() ? 'Actif' : 'Inactif',
            'isActive' => $station->getIsActive(),
        ];
    }

    public function getAllStationsFormatted(): array
    {
        $stations = $this->stationRepository->findBy([], ['id' => 'DESC']);
        return array_map(function(Station $station) {
            return $this->formatStation($station);
        }, $stations);
    }

    public function findStation(?string $uuid): ?Station
    {
        if (!$uuid) {
            return null;
        }
        $station = $this->stationRepository->findOneBy(['uuid' => $uuid]);
        if (!$station && is_numeric($uuid)) {
            $station = $this->stationRepository->find((int)$uuid);
        }
        return $station;
    }

    public function deleteByUuid(string $uuid): void
    {
        $station = $this->findStation($uuid);
        if (!$station) {
            throw new ExceptionApi('Gare introuvable.', ['msg' => 'Gare introuvable'], Response::HTTP_NOT_FOUND);
        }
        $this->delete($station);
    }

    public function toggleByUuid(string $uuid): Station
    {
        $station = $this->findStation($uuid);
        if (!$station) {
            throw new ExceptionApi('Gare introuvable.', ['msg' => 'Gare introuvable'], Response::HTTP_NOT_FOUND);
        }
        return $this->toggle($station);
    }
}
