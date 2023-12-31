<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserPasswordType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class UserController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * This controller allow us to edit user's profile
     * @param UserRepository $repository
     * @param int $id
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @return Response
     */
    #[Route('/utilisateur/edition/{id}', name: 'user.edit', methods: ['GET', 'POST'])]
    public function edit(UserRepository $repository, int $id, EntityManagerInterface $manager, Request $request,
                         UserPasswordHasherInterface $hasher):
    Response
    {
        $user = $repository->findOneBy(["id" => $id]);
        if ($this->security->isGranted('ROLE_USER') && $user === $this->security->getUser()) {
            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if ($hasher->isPasswordValid($user, $form->getData()->getPlainPassword())) {
                    $user = $form->getData();
                    $manager->persist($user);
                    $manager->flush();
                    $this->addFlash('success', 'Les informations de votre compte ont bien été modifiées');
                    return $this->redirectToRoute('recipe.index');
                } else {
                    $this->addFlash('warning', 'Le mot de passe renseigné est incorrect');
                }
            }
            return $this->render('pages/user/edit.html.twig', [
                'form' => $form->createView(),
            ]);
        } elseif ($this->security->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('recipe.index');
        } else {
            return $this->redirectToRoute('security.login');
        }
    }

    /**
     * This controller allow us to edit the user password
     * @param UserRepository $repository
     * @param int $id
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @return Response
     */
    #[Route('/utilisateur/edition-mot-de-passe/{id}', 'user.edit.password', methods: ['GET', 'POST'])]
    public function editPassword(UserRepository $repository, int $id, EntityManagerInterface $manager, Request
    $request, UserPasswordHasherInterface $hasher) :
    Response
    {
        $user = $repository->findOneBy(["id" => $id]);
        if ($this->security->isGranted('ROLE_USER') && $user === $this->security->getUser()) {
            $form = $this->createForm(UserPasswordType::class, $user);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                if ($hasher->isPasswordValid($user, $form->getData()->getPlainPassword())) {
                    $user->setPlainPassword($form->getData()->getNewPassword());
                    $user->setPassword($hasher->hashPassword($user, $form->getData()->getPlainPassword()));
                    $manager->persist($user);
                    $manager->flush();
                    $this->addFlash('success', 'Le mot de passe a été modifié');
                    return $this->redirectToRoute('recipe.index');
                } else {
                    $this->addFlash('warning', 'Le mot de passe renseigné est incorrect');
                }
            }
            return $this->render('pages/user/edit_password.html.twig', [
                'form' => $form->createView()
            ]);
        } elseif ($this->security->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('recipe.index');
        } else {
            return $this->redirectToRoute('security.login');
        }
    }
}
