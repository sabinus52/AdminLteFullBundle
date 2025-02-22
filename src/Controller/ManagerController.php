<?php

declare(strict_types=1);

/**
 * This file is part of OlixBackOfficeBundle.
 * (c) Sabinus52 <sabinus52@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Olix\BackOfficeBundle\Controller;

use Olix\BackOfficeBundle\Enum\ColorBS;
use Olix\BackOfficeBundle\Helper\ParameterOlix;
use Olix\BackOfficeBundle\Security\UserDatatable;
use Olix\BackOfficeBundle\Security\UserManager;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller des pages de la gestion des utilisateurs.
 *
 * @author     Sabinus52 <sabinus52@gmail.com>
 */
#[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access the admin dashboard.')]
class ManagerController extends AbstractController
{
    /**
     * Constructeur.
     */
    public function __construct(private readonly ParameterOlix $parameterOlix)
    {
    }

    /**
     * Affichage de la liste des utilisateurs.
     */
    #[Route(path: '/security/users', name: 'olix_users__list')]
    public function listUsers(UserManager $manager, Request $request, DataTableFactory $factory): Response
    {
        $this->checkAccess();

        $datatable = $factory->createFromType(UserDatatable::class, [
            'entity' => $manager->getClass(),
            'delay' => $this->parameterOlix->getValue('security.delay_activity'),
        ], [
            'searching' => true,
        ])
            ->handleRequest($request)
        ;

        if ($datatable->isCallback()) {
            return $datatable->getResponse();
        }

        return $this->render('@OlixBackOffice/Security/users-list.html.twig', [
            'datatable' => $datatable,
        ]);
    }

    /**
     * Création d'un nouvel utilisateur.
     */
    #[Route(path: '/security/users/create', name: 'olix_users__create')]
    public function createUser(UserManager $manager, Request $request): Response
    {
        $this->checkAccess();

        // Initialize new user
        $manager->newUser();

        // Create form and upgrade on validation form
        $form = $manager->createFormCreateUser();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Add this new user
            $manager->setUser($form->getData()); // @phpstan-ignore argument.type
            $manager->add((string) $form->get('password')->getData()); // @phpstan-ignore cast.string

            $this->addFlash(ColorBS::SUCCESS->value, sprintf("La création de l'utilisateur <b>%s</b> a bien été prise en compte", $manager->getUser()->getUserIdentifier()));

            return $this->redirectToRoute('olix_users__edit', ['id' => $manager->getUser()->getId()]);
        }

        return $this->render('@OlixBackOffice/Security/users-create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Modification de l'utilisateur.
     */
    #[Route(path: '/security/users/edit/{id}', name: 'olix_users__edit')]
    public function editUser(UserManager $manager, Request $request): Response
    {
        $this->checkAccess();
        $idUser = (int) $request->get('id'); /** @phpstan-ignore cast.int */

        // Get user from request
        $user = $manager->setUserById($idUser);
        if (!$user instanceof UserInterface) {
            return $this->redirectToRoute('olix_users__list');
        }

        // Create form and upgrade on validation form
        $form = $manager->createFormEditUser();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Update datas of this user
            $manager->setUser($form->getData())->update(); // @phpstan-ignore argument.type

            $this->addFlash(ColorBS::SUCCESS->value, sprintf("La modification de l'utilisateur <b>%s</b> a bien été prise en compte", $user->getUserIdentifier()));

            return $this->redirectToRoute('olix_users__list');
        }

        return $this->render('@OlixBackOffice/Security/users-edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * Change le mot de passe de l'utilisateur.
     */
    #[Route(path: '/security/users/password/{id}', name: 'olix_users__password')]
    public function changePassword(UserManager $manager, Request $request): Response
    {
        $this->checkAccess();
        $idUser = (int) $request->get('id'); /** @phpstan-ignore cast.int */

        // Get user from request
        $user = $manager->setUserById($idUser);
        if (!$user instanceof UserInterface) {
            return $this->redirectToRoute('olix_users__list');
        }

        // Create form and upgrade on validation form
        $form = $manager->createFormChangePassword();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Change password for this user
            $manager->update((string) $form->get('password')->getData()); // @phpstan-ignore cast.string

            $this->addFlash(ColorBS::SUCCESS->value, sprintf("La modification du mot de passe de l'utilisateur <b>%s</b> a bien été prise en compte", $user->getUserIdentifier()));

            return $this->redirectToRoute('olix_users__list');
        }

        return $this->render('@OlixBackOffice/Security/users-password.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * Suppression d'un utilisateur.
     */
    #[Route(path: '/security/users/remove/{id}', name: 'olix_users__remove')]
    public function removeUser(UserManager $manager, Request $request): Response
    {
        $this->checkAccess();

        // Get user from request
        $idUser = (int) $request->get('id'); /** @phpstan-ignore cast.int */
        $user = $manager->setUserById($idUser);
        if (!$user instanceof UserInterface) {
            return $this->redirectToRoute('olix_users__list');
        }

        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Remove this user
            $manager->remove();

            $this->addFlash(ColorBS::SUCCESS->value, sprintf("La suppression de l'utilisateur <b>%s</b> a bien été prise en compte", $user->getUserIdentifier()));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Modal/form-delete.html.twig', [
            'form' => $form,
            'element' => sprintf("l'utilisateur <strong>%s</strong>", $user->getUserIdentifier()),
        ]);
    }

    /**
     * Vérifie si on autorise en fonction du paramètre "security.menu_activ".
     */
    protected function checkAccess(): bool
    {
        if (true !== $this->parameterOlix->getValue('security.menu_activ')) {
            throw new \Exception('Asses denied', 1); // FIXME
        }

        return true;
    }
}
