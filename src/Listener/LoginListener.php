<?php

/**
 *  This file is part of OlixBackOfficeBundle.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Olix\BackOfficeBundle\Listener;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Olix\BackOfficeBundle\Model\User;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Listener sur la connexion de l'utilisateur.
 *
 * @author     Sabinus52 <sabinus52@gmail.com>
 */
class LoginListener
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Evenement au moment de la connexion de l'utilisateur.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        // Get the User entity.
        $user = $event->getAuthenticationToken()->getUser();

        // Mise à jour de la date de login
        /** @var User $user */
        $user->setLastLogin(new DateTime());

        // Persist the data to database.
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
