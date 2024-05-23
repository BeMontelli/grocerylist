<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\User;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route("/{_locale}/admin/recipes", name: "admin.recipe.", requirements: ['_locale' => 'fr|en'])]
#[IsGranted('ROLE_ADMIN')]
class RecipeController extends AbstractController
{
    private $security;
    private $fileUploader;

    public function __construct(Security $security, FileUploader $fileUploader)
    {
        $this->security = $security;
        $this->fileUploader = $fileUploader;
    }

    #[Route('/', name: 'index')]
    public function index(Request $request, RecipeRepository $recipeRepository): Response
    {
        /** @var $user User */
        $user = $this->security->getUser();

        $currentPage = $request->query->getInt('page', 1);
        $recipes = $recipeRepository->paginateUserRecipes($currentPage,$user);

        return $this->render('admin/recipe/index.html.twig', [
            'recipes' => $recipes
        ]);
    }

    #[Route('/create/', name: 'create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var $user User */
        $user = $this->security->getUser();

        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class,$recipe);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('thumbnailfile')->getData();
            if ($file) $thumbnailPath = $this->fileUploader->uploadRecipeThumbnail($file);

            /** @var Recipe $recipe */
            $recipe = $form->getData();
            if($thumbnailPath) $recipe->setThumbnail($thumbnailPath);

            $recipe = $form->getData();
            $recipe->setUser($user);
            $entityManager->persist($recipe);
            $entityManager->flush();
            $this->addFlash('success', 'Recipe saved !');
            return $this->redirectToRoute('admin.recipe.index');
        }

        return $this->render('admin/recipe/create.html.twig',[
            'form' => $form
        ]);
    }

    #[Route('/{slug}-{id}', name: 'show', requirements: ['id' => Requirement::DIGITS, 'slug' => Requirement::ASCII_SLUG])]
    public function show(string $slug, int $id, RecipeRepository $recipeRepository): Response
    {
        return $this->render('admin/recipe/show.html.twig', [
            'recipe' => $recipeRepository->findRecipeWithCategory($id),
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', requirements: ['id' => Requirement::DIGITS], methods: ['GET','POST'])]
    public function edit(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {

            /** @var Recipe $recipe */
            $recipe = $form->getData();
            $oldThumbnail = $recipe->getThumbnail();

            $file = $form->get('thumbnailfile')->getData();
            if ($file) $thumbnailPath = $this->fileUploader->uploadRecipeThumbnail($file);

            $fileDir = $this->getParameter('kernel.project_dir').'/public';
            $this->fileUploader->deleteThumbnail($fileDir,$oldThumbnail);

            /** @var Recipe $recipe */
            if($thumbnailPath) $recipe->setThumbnail($thumbnailPath);

            $entityManager->persist($recipe);
            $entityManager->flush();
            $this->addFlash('success', 'Recipe updated !');
            return $this->redirectToRoute('admin.recipe.edit', ["id" => $recipe->getId()]);
        }

        return $this->render('admin/recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(Recipe $recipe, EntityManagerInterface $entityManager) {
        $currentThumbnail = $recipe->getThumbnail();
        $fileDir = $this->getParameter('kernel.project_dir').'/public';
        $this->fileUploader->deleteThumbnail($fileDir,$currentThumbnail);

        $entityManager->remove($recipe);
        $entityManager->flush();
        $this->addFlash('success', 'Recipe '.$recipe->getTitle().' deleted !');
        return $this->redirectToRoute('admin.recipe.index');
    }
}
