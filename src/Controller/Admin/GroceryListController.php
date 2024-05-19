<?php

namespace App\Controller\Admin;

use App\Entity\GroceryList;
use App\Form\GroceryListType;
use App\Repository\GroceryListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/admin/lists", name: "admin.list.")]
#[IsGranted('ROLE_ADMIN')]
class GroceryListController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, GroceryListRepository $groceryListRepository): Response
    {
        $currentPage = $request->query->getInt('page', 1);
        $lists = $groceryListRepository->paginateLists($currentPage);
        return $this->render('admin/grocery_list/index.html.twig', [
            'grocery_lists' => $lists,
        ]);
    }

    #[Route('/new/', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $groceryList = new GroceryList();
        $form = $this->createForm(GroceryListType::class, $groceryList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($groceryList);
            $entityManager->flush();

            return $this->redirectToRoute('admin.list.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/grocery_list/new.html.twig', [
            'grocery_list' => $groceryList,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}-{id}', name: 'show', requirements: ['id' => Requirement::DIGITS, 'slug' => Requirement::ASCII_SLUG])]
    public function show(GroceryList $groceryList): Response
    {
        return $this->render('admin/grocery_list/show.html.twig', [
            'grocery_list' => $groceryList,
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', requirements: ['id' => Requirement::DIGITS], methods: ['GET','POST'])]
    public function edit(Request $request, GroceryList $groceryList, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GroceryListType::class, $groceryList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin.list.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/grocery_list/edit.html.twig', [
            'grocery_list' => $groceryList,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    public function delete(Request $request, GroceryList $groceryList, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$groceryList->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($groceryList);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin.list.index', [], Response::HTTP_SEE_OTHER);
    }
}