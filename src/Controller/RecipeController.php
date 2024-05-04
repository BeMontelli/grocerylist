<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecipeController extends AbstractController
{
    #[Route('/recipes/', name: 'recipe.index')]
    public function index(RecipeRepository $recipeRepository): Response
    {
        /*$recipe = new Recipe();
        $recipe->setTitle("Burger")
            ->setSlug("burger")
            ->setPrice(5.80)
            ->setContent("<b>test</b> fsegrd")
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($recipe);
        $entityManager->flush();*/

        /*$entityManager->remove($recipe);*/

        return $this->render('recipe/index.html.twig', [
            'recipes' => $recipeRepository->findAll()
        ]);
    }

    #[Route('/recipes/create/', name: 'recipe.create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class,$recipe);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $recipe->setCreatedAt(new \DateTimeImmutable());
            $recipe->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($recipe);
            $entityManager->flush();
            $this->addFlash('success', 'Recipe saved !');
            return $this->redirectToRoute('recipe.index');
        }

        return $this->render('recipe/create.html.twig',[
            'form' => $form
        ]);
    }

    #[Route('/recipes/{slug}-{id}', name: 'recipe.show', requirements: ['id' => '\d+', 'slug' => '[a-z0-9-]+'])]
    public function show(string $slug, int $id, RecipeRepository $recipeRepository): Response
    {
        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipeRepository->find($id),
        ]);
    }

    #[Route('/recipes/edit/{id}', name: 'recipe.edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $formData->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($formData);
            $entityManager->flush();
            $this->addFlash('success', 'Recipe updated !');
            return $this->redirectToRoute('recipe.edit', ["id" => $recipe->getId()]);
        }

        return $this->render('recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form
        ]);
    }
}
