<?php

namespace App\Service;

use App\Entity\Admin\User;
use App\Entity\Business\Passenger;
use App\Entity\Extra\Notification;
use App\Repository\Admin\UserRepository;
use App\Repository\Extra\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private $em;
    private $userRepository;
    private $notificationRepository;
    private $fcmPushService;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        NotificationRepository $notificationRepository,
        FcmPushService $fcmPushService
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->notificationRepository = $notificationRepository;
        $this->fcmPushService = $fcmPushService;
    }

    public function createNotification(User $user, string $title, string $message, string $type = 'SYSTEM'): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setType($type);
        $notification->setIsRead(false);

        $this->em->persist($notification);
        $this->em->flush();

        try {
            $this->fcmPushService->sendToUser($user, $title, $message, ['type' => $type]);
        } catch (\Exception $e) {
        }

        return $notification;
    }

    public function getOrCreateUserForPassenger(Passenger $passenger): User
    {
        $user = $this->userRepository->findOneBy(['passenger' => $passenger]);
        if (!$user) {
            $phoneClean = preg_replace('/[^0-9]/', '', $passenger->getPhoneNumber() ?? '');
            if ($phoneClean) {
                $user = $this->userRepository->findOneBy(['username' => $phoneClean])
                     ?? $this->userRepository->findOneBy(['username' => $phoneClean . '_PASSENGER'])
                     ?? $this->userRepository->findOneBy(['telephone' => $passenger->getPhoneNumber()]);
            }
        }

        if (!$user) {
            $phoneClean = preg_replace('/[^0-9]/', '', $passenger->getPhoneNumber() ?? '');
            $username = $phoneClean ?: 'user_' . uniqid();

            $user = new User();
            $user->setType('PASSENGER');
            $user->setUsername($username);
            $user->setTelephone($passenger->getPhoneNumber() ?? '');
            $user->setNom($passenger->getLastname() ?? 'Passager');
            $user->setPrenom($passenger->getFirstname() ?? '');
            $user->setEmail($passenger->getEmail());
            $user->setPassenger($passenger);
            $user->setPassword('dummy_pass');

            $this->em->persist($user);
            $this->em->flush();
        } elseif (!$user->getPassenger()) {
            $user->setPassenger($passenger);
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    public function createNotificationForPassenger(Passenger $passenger, string $title, string $message, string $type = 'SYSTEM'): Notification
    {
        $user = $this->getOrCreateUserForPassenger($passenger);
        return $this->createNotification($user, $title, $message, $type);
    }

    public function getOrCreateUserForAgent(\App\Entity\Business\Agent $agent): User
    {
        $user = $this->userRepository->findOneBy(['agent' => $agent]);
        if (!$user) {
            $phoneClean = preg_replace('/[^0-9]/', '', $agent->getPhoneNumber() ?? '');
            if ($phoneClean) {
                $user = $this->userRepository->findOneBy(['username' => $phoneClean . '_AGENT'])
                     ?? $this->userRepository->findOneBy(['username' => $phoneClean])
                     ?? $this->userRepository->findOneBy(['telephone' => $agent->getPhoneNumber()]);
            }
        }

        if (!$user) {
            $phoneClean = preg_replace('/[^0-9]/', '', $agent->getPhoneNumber() ?? '');
            $username = $phoneClean ? $phoneClean . '_AGENT' : 'agent_' . uniqid();

            $user = new User();
            $user->setType(User::TYPE['AGENT']);
            $user->setUsername($username);
            $user->setTelephone($agent->getPhoneNumber() ?? '');
            $user->setNom($agent->getLastname() ?? 'Agent');
            $user->setPrenom($agent->getFirstname() ?? 'Gare');
            $user->setAgent($agent);
            $user->setIsEnabled(true);
            $user->setPassword('dummy_pass');

            $this->em->persist($user);
            $this->em->flush();
        } elseif (!$user->getAgent()) {
            $user->setAgent($agent);
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    public function createNotificationForAgent(\App\Entity\Business\Agent $agent, string $title, string $message, string $type = 'SYSTEM'): Notification
    {
        $user = $this->getOrCreateUserForAgent($agent);
        return $this->createNotification($user, $title, $message, $type);
    }
}
