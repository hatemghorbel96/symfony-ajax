<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Entity\Category;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

 #[Route('/post')] 
class PostController extends AbstractController
{
    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository,ManagerRegistry $doctrine): Response
    {
        $posts = $doctrine->getRepository(Post::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $maxprice = $doctrine->getRepository(Post::class)->getmaxprice();
        return $this->render('post/index.html.twig', [
           
            'posts' =>  $posts, 'categories' => $categories,'maxprice'=>$maxprice
        ]);
    }


    #[Route('/pagination', name: 'app_post_index_page', methods: ['GET'])]
    public function indexpagination(PostRepository $postRepository,ManagerRegistry $doctrine): Response
    {
        $posts = $doctrine->getRepository(Post::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $maxprice = $doctrine->getRepository(Post::class)->getmaxprice();
        return $this->render('post/indexpagination.html.twig', [
           
            'posts' =>  $posts, 'categories' => $categories,'maxprice'=>$maxprice
        ]);
    }


    #[Route('/deletepage', name: 'index_delete', methods: ['GET'])]
    public function indexdelete(PostRepository $postRepository,ManagerRegistry $doctrine): Response
    {
        $posts = $doctrine->getRepository(Post::class)->findAll();     
        return $this->render('post/indexdel.html.twig', [         
            'posts' =>  $posts, 
        ]);
    }

    #[Route('/deletemultiple', name: 'post_delete_multiple', methods: ['POST'])]
    public function deleteMultiple(Request $request, PostRepository $postRepository, ManagerRegistry $doctrine): JsonResponse
    {
         /* $ids = $request->query->get('ids'); */  // parameter
         /* $ids=$request->request->get('ids'); */   //request body
         
       $ids = explode(',', $request->request->get('ids'));
    if ($ids === null || empty($ids)) {
        return $this->json([
            'success' => false,
            'message' => 'Please select at least one post to delete.'
        ]);
    }

    $em = $doctrine->getManager();
    $deletedIds = [];
   
    $posts = $postRepository->findById($ids);
    if ($posts) {
        foreach ($posts as $post) {
            $deletedIds[] = $post->getId();
            $em->remove($post);
        }
        $em->flush();
        return $this->json([
            'success' => true,
            'message' => 'Posts deleted successfully.',
            'deletedIds' => $deletedIds
        ]);
    }
    return $this->json([
        'success' => false,
        'message' => 'No posts found to delete.'
    ]);
}

    #[Route('/modal', name: 'index_modal', methods: ['GET'])]
public function indexmodal(PostRepository $postRepository, ManagerRegistry $doctrine, Post $post = null, Request $request,PaginatorInterface $paginator): Response
{
    $p = $doctrine->getRepository(Post::class)->findAll();

    $posts = $paginator->paginate(
        $p,
        $request->query->getInt('page', 1),
        2
    );
    $form = $this->createForm(PostType::class, $post);

    // handle form submission
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager = $doctrine->getManager();
        $entityManager->persist($post);
        $entityManager->flush();

       // return updated post data as JSON
       return new JsonResponse([
        'id' => $post->getId(),
        'name' => $post->getName(),
        'category' => $post->getCategory()->getName(),
    ]);
}

    return $this->render('post/indexmodal.html.twig', [
        'posts' => $posts,
        'post' => $post,
        'form' => $form->createView() 
    ]);
}


    #[Route('/product/search', name:'product_search')]
    public function search(Request $request, PostRepository $postRepository, SerializerInterface $serializer,ManagerRegistry $doctrine): JsonResponse
    {
        $query = $request->query->get('q');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $categories = $request->query->get('categorielist');

        $entityManager = $doctrine->getManager();
       
        $qb = $entityManager->getRepository(Post::class)->createQueryBuilder('p');
    
        if (!empty($query)) {
            $qb->andWhere('p.name LIKE :query')
                ->setParameter('query', $query.'%');
        }
    
        if (!empty($minPrice)) {
            $qb->andWhere('p.price >= :min_price')
               ->setParameter('min_price', $minPrice);
        }
    
        if (!empty($maxPrice)) {
            $qb->andWhere('p.price <= :max_price')
               ->setParameter('max_price', $maxPrice);
        }

      
        if (!empty($categories)) {
            $categories = explode(',', $categories);
            $qb->andWhere('p.category IN (:categories)')
               ->setParameter('categories', $categories);
        }
        
    
        $posts = $qb->getQuery()->getResult();
    
        $response = array();
        foreach ($posts as $p) {
            $response[] = array(
                'id' => $p->getId(),
                'name' => $p->getName(),
                'price' => $p->getPrice(),
                'description'=> $p->getDescription(),
                'category'=> $p->getCategory()->getName(),
            );
        }
    
        return new JsonResponse($response);
    }

    #[Route('/product/searchcat', name:'product_searchcat')]
    public function searchwithcategory(Request $request, PostRepository $postRepository, SerializerInterface $serializer,ManagerRegistry $doctrine): JsonResponse
    {
        $query = $request->query->get('q');
        $categories = $request->query->get('categories');
       $entityManager = $doctrine->getManager();
       
       $qb = $postRepository->createQueryBuilder('p');

       if (!empty($query)) {
           $qb->andWhere('p.name LIKE :query')
              ->setParameter('query', $query.'%');
       }
   
       if (!empty($categories)) {
           $qb->join('p.category', 'c')
              ->andWhere('c.id IN (:categories)')
              ->setParameter('categories', $categories);
       }
   
       $posts = $qb->getQuery()->getResult();
   
       $response = [];
        foreach ($posts as $p) {
            $response[] = array(
                'id' => $p->getId(),
                'name' => $p->getName(),
                'description'=> $p->getDescription(),
                'category'=> $p->getCategory()->getName(),
                
            );
        }
         return new JsonResponse($response);
    }
    //composer require knplabs/knp-paginator-bundle
    #[Route('/product/searchpagination', name:'product_pagination')]
    public function search2(Request $request, PostRepository $postRepository, SerializerInterface $serializer,ManagerRegistry $doctrine,PaginatorInterface $paginator): JsonResponse
    {
        $query = $request->query->get('q');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $categories = $request->query->get('categorielist');

        $entityManager = $doctrine->getManager();
       
        $qb = $entityManager->getRepository(Post::class)->createQueryBuilder('p');
    
        if (!empty($query)) {
            $qb->andWhere('p.name LIKE :query')
                ->setParameter('query', $query.'%');
        }
    
        if (!empty($minPrice)) {
            $qb->andWhere('p.price >= :min_price')
               ->setParameter('min_price', $minPrice);
        }
    
        if (!empty($maxPrice)) {
            $qb->andWhere('p.price <= :max_price')
               ->setParameter('max_price', $maxPrice);
        }

      
        if (!empty($categories)) {
            $categories = explode(',', $categories);
            $qb->andWhere('p.category IN (:categories)')
               ->setParameter('categories', $categories);
        }

        $posts = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            5
        );
        
    
      /*   $posts = $qb->getQuery()->getResult(); */
    
        $response = array();
        foreach ($posts as $p) {
            $response[] = array(
                'id' => $p->getId(),
                'name' => $p->getName(),
                'price' => $p->getPrice(),
                'description'=> $p->getDescription(),
                'category'=> $p->getCategory()->getName(),
            );
        }
    
        return new JsonResponse($response);
    }

   
   

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PostRepository $postRepository,ManagerRegistry $doctrine,SerializerInterface $serializer): Response
    {
       
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        $posts = $postRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->save($post, true);
            
            // Get all posts including the newly created one
            $posts = $postRepository->findAll();
            
            // Prepare the data array
            $data = array();
            foreach ($posts as $p) {
                $data[] = array(
                    'id' => $p->getId(),
                    'name' => $p->getName(),
                    'price' => $p->getPrice(),
                    'description'=> $p->getDescription(),
                    'category'=> $p->getCategory()->getName(),
                );
            }
        
            return new JsonResponse($data);
        }

        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
            'posts' => $posts,
        ]);
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show($id,ManagerRegistry $doctrine): Response
    {   $entityManager = $doctrine->getManager();
        $post = $entityManager->getRepository(Post::class)->find($id);
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, PostRepository $postRepository): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->save($post, true);

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }


    #[Route('/{id}/edit2', name: 'app_post_edit_ajax', methods: ['GET', 'POST'])]
    public function editAjax($id, Request $request, PostRepository $postRepository,ManagerRegistry $doctrine): JsonResponse
    {
        $post = $postRepository->find($id);
        
        if ($request->isMethod('POST')) {
            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Get the data from the form
                $postData = $form->getData();
        
                // Update the post with the submitted form data
                $post->setName($postData->getName());
                $post->setDescription($postData->getDescription());
                $post->setPrice($postData->getPrice());
                $post->setCategory($postData->getCategory());
        
                // Save the changes to the database
                $entityManager = $doctrine->getManager();
                $entityManager->persist($post);
                $entityManager->flush();
                
              // Create an array of data to return as the JSON response
                    $data = [
                        'id' => $post->getId(),
                        'name' => $post->getName(),
                        'description' => $post->getDescription(),
                        'price' => $post->getPrice(),
                        'category' => $post->getCategory()->getName(),
                    ];

                    // Return the updated data as a JSON response
                    return $this->json($data);
                } else {
                    // Return an error response
                    return $this->json(['success' => false]);
                }
            }

        return $this->json([
            'name' => $post->getName(),
            'description' => $post->getDescription(),
            'id' => $post->getId(),
            'price'=>$post->getPrice(),
            'cat'=>$post->getCat(),
            'category'=> $post->getCategory()->getName(),
        ]);
    }

    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, PostRepository $postRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $postRepository->remove($post, true);
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

     /* // Update the post with the submitted form data
            $post->setName($request->request->get('post_name'));
            $post->setDescription($request->request->get('post_description'));
            $post->setPrice($request->request->get('post_price'));
            $category = $doctrine->getRepository(Category::class)->find($request->request->get('post_category'));
            $post->setCategory($category);
            
            // Save the changes to the database
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
            
            // Return a success response
            return $this->json(['success' => true]); */
}
