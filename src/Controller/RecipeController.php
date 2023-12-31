<?php

namespace App\Controller;

use App\Entity\Mark;
use App\Entity\Recipe;
use App\Form\MarkType;
use App\Form\RecipeType;
use App\Repository\IngredientRepository;
use App\Repository\MarkRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class RecipeController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * This controller allow us to create a new recipe
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route('/recette/creation', 'recipe.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager) : Response
    {
        if ($this->security->isGranted('ROLE_USER')) {
            $recipe = new Recipe();
            $form = $this->createForm(RecipeType::class, $recipe);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $recipe = $form->getData();
                $recipe->setUser($this->getUser());
                $recipe->setUser($this->getUser());
                $manager->persist($recipe);
                $manager->flush();
                $this->addFlash(
                    'success',
                    'Votre recette à été crée avec succès !'
                );
                return $this->redirectToRoute('recipe.index');
            }
            return $this->render('pages/recipe/new.html.twig', [
                'form' => $form->createView()
            ]);
        } else {
            return $this->redirectToRoute('security.login');
        }

    }

    /**
     *  This controller display all the recipes
     * @param RecipeRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */
    #[Route('/recette', name: 'recipe.index', methods: ['GET'])]
    public function index(RecipeRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        if ($this->security->isGranted('ROLE_USER')) {
            $recipes = $paginator->paginate(
                $repository->findBy(['user' => $this->getUser()]),
                $request->query->getInt('page', 1),
                10
            );

            return $this->render('pages/recipe/index.html.twig', [
                'recipes' => $recipes,
            ]);
        } else {
            return $this->redirectToRoute('security.login');
        }
    }

    /**
     * This controller allow us to see a recipe if this one is public
     * @return Response
     */
    #[Route('/recette/communaute', 'recipe.community', methods: ['GET'])]
    public function indexPublic(RecipeRepository $repository, PaginatorInterface $paginator, Request $request) : Response
    {
        $recipes = $paginator->paginate(
            $repository->findPublicRecipe(null),
            $request->query->getInt('page', 1),
            10
        );
        return $this->render('pages/recipe/index_public.html.twig', [
            'recipes' => $recipes
        ]);
    }

    #[Route('/recette/{id}', 'recipe.show', methods: ['GET','POST'])]
    public function show(int $id, RecipeRepository $repository, Request $request, EntityManagerInterface
$manager,
                         MarkRepository $markRepository): Response
    {
        $recipe = $repository->findOneBy(["id" => $id]);
        if (($this->security->isGranted('ROLE_USER') && isset($recipe) && $recipe->getIsPublic() === true) ||
            ($this->security->isGranted('ROLE_USER') && isset($recipe) && $recipe->getUser() ===
        $this->security->getUser())) {
            $mark = new Mark();
            $form = $this->createForm(MarkType::class, $mark);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $mark->setUser($this->getUser())
                    ->setRecipe($recipe);
                $existingMark = $markRepository->findOneBy([
                   'user' => $this->getUser(),
                   'recipe' => $recipe
                ]);
                if (!$existingMark) {
                    $manager->persist($mark);
                } else {
                    $existingMark->setMark(
                        $form->getData()->getMark()
                    );
                }
                $manager->flush();
                $this->addFlash('success', 'Votre note à été bien prise en compte.');
                return $this->redirectToRoute('recipe.show', ['id' => $recipe->getId()]);
            }
            return $this->render('pages/recipe/show.html.twig', [
                'recipe' => $recipe,
                'form' => $form->createView(),
            ]);
        } elseif ($this->security->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('recipe.index');
        } else {
            return $this->redirectToRoute('security.login');
        }
    }

    /**
     * This controller allow us to edit a recipe
     * @param RecipeRepository $repository
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route('/recette/edition/{id}', 'recipe.edit', methods: ['GET', 'POST'])]
    public function edit(RecipeRepository $repository, int $id, Request $request,
EntityManagerInterface $manager) : Response
    {
        $recipe = $repository->findOneBy(["id" => $id]);
        if ($this->security->isGranted('ROLE_USER') && $this->security->getUser() === $recipe->getUser()) {
            $form = $this->createForm(RecipeType::class, $recipe);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $recipe = $form->getData();
                $manager->persist($recipe);
                $manager->flush();
                $this->addFlash('success', 'Votre recipe à été modifié avec succès !');

                return $this->redirectToRoute('recipe.index');
            }
            return $this->render('pages/recipe/edit.html.twig', [
                'form' => $form->createView()
            ]);
        } else {
            return $this->redirectToRoute('recipe.index');
        }
    }

    #[Route('/recette/supprimer/{id}', 'recipe.delete', methods: ['GET'])]
    public function delete(RecipeRepository $repository, int $id, EntityManagerInterface $manager) :
    Response
    {
        $recipe = $repository->findOneBy(["id" => $id]);
        if ($this->security->isGranted('USER_ROLE') && $recipe->getUser() === $this->security->getUser()) {
            $manager->remove($recipe);
            $manager->flush();
            $this->addFlash('success', 'Votre recette à été supprimé avec succès !');
        }
        return $this->redirectToRoute('recipe.index');
    }
}
