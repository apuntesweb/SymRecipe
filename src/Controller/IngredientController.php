<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Form\IngredientType;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class IngredientController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    /**
     * This function display all ingredients
     * @param IngredientRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */

    #[Route('/ingredient', name: 'ingredient.index', methods: ['GET'])]
    public function index(IngredientRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        if ($this->security->isGranted('ROLE_USER')){
            $ingredients = $paginator->paginate(
                $repository->findBy(['user' => $this->getUser()]),
                $request->query->getInt('page', 1),
                10
            );

            return $this->render('pages/ingredient/index.html.twig', [
                'ingredients' => $ingredients
            ]);
        } else {
            return $this->redirectToRoute('security.login');
        }
    }

    #[Route('/ingredient/nouveau', name:'ingredient.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager) : Response
    {
        if ($this->security->isGranted('ROLE_USER')){
            $ingredient = new Ingredient();
            $form = $this->createForm(IngredientType::class, $ingredient);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $ingredient = $form->getData();
                $ingredient->setUser($this->getUser());
                $manager->persist($ingredient);
                $manager->flush();
                $this->addFlash(
                    'success',
                    'Votre ingrédient à été crée avec succès !'
                );
                return $this->redirectToRoute('ingredient.index');
            }
            return  $this->render('pages/ingredient/new.html.twig', [
                'form'=> $form->createView()
            ]);
        } else {
            return $this->redirectToRoute('security.login');
        }
    }

    #[Route('/ingredient/edition/{id}', 'ingredient.edit', methods: ['GET', 'POST'])]
    public function edit(IngredientRepository $repository, int $id, Request $request, EntityManagerInterface
    $manager) : Response
    {
        $ingredient = $repository->findOneBy(["id" => $id]);
        if ($this->security->isGranted('ROLE_USER') && $this->security->getUser() === $ingredient->getUser()){
            $form = $this->createForm(IngredientType::class, $ingredient);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $ingredient = $form->getData();
                $manager->persist($ingredient);
                $manager->flush();
                $this->addFlash('success', 'Votre ingrédient à été modifié avec succès !');

                return $this->redirectToRoute('ingredient.index');
            }
            return $this->render('pages/ingredient/edit.html.twig', [
                'form' => $form->createView()
            ]);
        } else {
            return $this->redirectToRoute('ingredient.index');
        }
    }

    #[Route('/ingredient/suppression/{id}', 'ingredient.delete', methods: ['GET'])]
    public function delete(IngredientRepository $repository, int $id, EntityManagerInterface
    $manager) :
Response
    {
        $ingredient = $repository->findOneBy(["id" => $id]);
        if ($this->security->isGranted('ROLE_USER') && $this->security->getUser() === $ingredient->getUser()) {
            $manager->remove($ingredient);
            $manager->flush();
            $this->addFlash('success', 'Votre ingrédient à été supprimé avec succès !');
        }
        return $this->redirectToRoute('ingredient.index');
    }
}
