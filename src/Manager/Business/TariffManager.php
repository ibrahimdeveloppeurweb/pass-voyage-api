<?php

namespace App\Manager\Business;

use App\Entity\Business\Company;
use App\Entity\Business\Route as TravelRoute;
use App\Entity\Business\Tariff;
use App\Exception\ExceptionApi;
use App\Repository\Business\CompanyRepository;
use App\Repository\Business\RouteRepository;
use App\Repository\Business\TariffRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class TariffManager
{
    private $em;
    private $tariffRepository;
    private $routeRepository;
    private $companyRepository;

    public function __construct(
        EntityManagerInterface $em,
        TariffRepository $tariffRepository,
        RouteRepository $routeRepository,
        CompanyRepository $companyRepository
    ) {
        $this->em = $em;
        $this->tariffRepository = $tariffRepository;
        $this->routeRepository = $routeRepository;
        $this->companyRepository = $companyRepository;
    }

    private function resolveRoute($routeInput): TravelRoute
    {
        if (empty($routeInput)) {
            throw new ExceptionApi('Le trajet est obligatoire.', ['msg' => 'Trajet obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (is_numeric($routeInput)) {
            $route = $this->routeRepository->find((int)$routeInput);
        } else {
            $route = $this->routeRepository->findOneBy(['uuid' => trim($routeInput)]);
            if (!$route) {
                // Try matching "Dep - Arr" or "Dep -> Arr"
                $parts = preg_split('/[\s\-\>]+/', trim($routeInput));
                if (count($parts) >= 2) {
                    $routes = $this->routeRepository->findAll();
                    foreach ($routes as $r) {
                        if ($r->getTitle() === trim($routeInput)) {
                            $route = $r;
                            break;
                        }
                    }
                }
            }
        }

        if (!$route) {
            throw new ExceptionApi('Trajet introuvable.', ['msg' => 'Trajet introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $route;
    }

    private function resolveCompany($companyInput): ?Company
    {
        if (empty($companyInput) || $companyInput === 'Général' || $companyInput === 'Général (Toutes)' || $companyInput === 'Toutes' || $companyInput === 'Toutes les compagnies') {
            return null;
        }

        if (is_numeric($companyInput)) {
            return $this->companyRepository->find((int)$companyInput);
        }

        return $this->companyRepository->findOneBy(['name' => trim($companyInput)])
            ?? $this->companyRepository->findOneBy(['uuid' => trim($companyInput)]);
    }

    public function create($data): Tariff
    {
        $dataObj = is_array($data) ? (object)$data : $data;

        $routeInput = $dataObj->route ?? $dataObj->routeUuid ?? $dataObj->routeId ?? $dataObj->trajet ?? null;
        $route = $this->resolveRoute($routeInput);

        $companyInput = $dataObj->company ?? $dataObj->compagnie ?? null;
        $company = $this->resolveCompany($companyInput);

        $price = (int)($dataObj->price ?? $dataObj->prix ?? $dataObj->prixBase ?? 0);
        if ($price <= 0) {
            throw new ExceptionApi('Le prix de base doit être supérieur à 0.', ['msg' => 'Prix invalide'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tariff = new Tariff();
        $tariff->setRoute($route);
        $tariff->setCompany($company);
        $tariff->setPrice($price);

        if (isset($dataObj->isActive)) {
            $tariff->setIsActive((bool)$dataObj->isActive);
        } else {
            $tariff->setIsActive(true);
        }

        $this->em->persist($tariff);
        $this->em->flush();

        return $tariff;
    }

    public function update(string $uuid, $data): Tariff
    {
        $tariff = $this->tariffRepository->findOneBy(['uuid' => $uuid]);
        if (!$tariff && is_numeric($uuid)) {
            $tariff = $this->tariffRepository->find((int)$uuid);
        }

        if (!$tariff) {
            throw new ExceptionApi('Tarif introuvable.', ['msg' => 'Tarif introuvable'], Response::HTTP_NOT_FOUND);
        }

        $dataObj = is_array($data) ? (object)$data : $data;

        if (isset($dataObj->route) || isset($dataObj->trajet)) {
            $routeInput = $dataObj->route ?? $dataObj->trajet;
            $route = $this->resolveRoute($routeInput);
            $tariff->setRoute($route);
        }

        if (isset($dataObj->company) || isset($dataObj->compagnie)) {
            $companyInput = $dataObj->company ?? $dataObj->compagnie;
            $company = $this->resolveCompany($companyInput);
            $tariff->setCompany($company);
        }

        if (isset($dataObj->price) || isset($dataObj->prix) || isset($dataObj->prixBase)) {
            $price = (int)($dataObj->price ?? $dataObj->prix ?? $dataObj->prixBase);
            if ($price > 0) {
                $tariff->setPrice($price);
            }
        }

        if (isset($dataObj->isActive)) {
            $tariff->setIsActive((bool)$dataObj->isActive);
        }

        $this->em->persist($tariff);
        $this->em->flush();

        return $tariff;
    }

    public function delete(Tariff $tariff): void
    {
        $this->em->remove($tariff);
        $this->em->flush();
    }

    public function formatTariff(Tariff $tariff, bool $includeNested = false): array
    {
        $routeName = $tariff->getRoute() ? $tariff->getRoute()->getTitle() : 'N/A';
        $companyName = $tariff->getCompany() ? $tariff->getCompany()->getName() : 'Toutes';

        $data = [
            'id' => $tariff->getId(),
            'uuid' => $tariff->getUuid(),
            'trajet' => $routeName,
            'compagnie' => $companyName,
            'prix' => $tariff->getPrice(),
            'prixFormatted' => number_format($tariff->getPrice() ?? 0, 0, '', ',') . ' XOF',
            'price' => $tariff->getPrice(),
            'isActive' => $tariff->getIsActive(),
        ];

        if ($includeNested) {
            $data['route'] = $tariff->getRoute() ? [
                'id' => $tariff->getRoute()->getId(),
                'uuid' => $tariff->getRoute()->getUuid(),
                'title' => $routeName,
            ] : null;
            $data['company'] = $tariff->getCompany() ? [
                'id' => $tariff->getCompany()->getId(),
                'uuid' => $tariff->getCompany()->getUuid(),
                'name' => $companyName,
            ] : null;
        }

        return $data;
    }

    public function getAllTariffsFormatted(): array
    {
        $tariffs = $this->tariffRepository->findBy([], ['id' => 'DESC']);
        return array_map(function(Tariff $tariff) {
            return $this->formatTariff($tariff, true);
        }, $tariffs);
    }

    public function findTariff(?string $uuid): ?Tariff
    {
        if (!$uuid) {
            return null;
        }
        $tariff = $this->tariffRepository->findOneBy(['uuid' => $uuid]);
        if (!$tariff && is_numeric($uuid)) {
            $tariff = $this->tariffRepository->find((int)$uuid);
        }
        return $tariff;
    }

    public function deleteByUuid(string $uuid): void
    {
        $tariff = $this->findTariff($uuid);
        if (!$tariff) {
            throw new ExceptionApi('Tarif introuvable.', ['msg' => 'Tarif introuvable'], Response::HTTP_NOT_FOUND);
        }
        $this->delete($tariff);
    }
}
