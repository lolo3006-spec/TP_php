<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Model\MovieFilterDTO;
use App\Model\MovieUpdateDTO;
use App\Model\PaginationDTO;
use App\Model\Paginator;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/movies')]
final class MoviesController extends AbstractController
{
    #[Route('', name: 'app_movies_list', methods: ['GET'])]
    public function list(
        MovieRepository $movieRepository,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PaginationDTO $pagination = new PaginationDTO(),
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        MovieFilterDTO $filter = new MovieFilterDTO(),
    ): JsonResponse {
        $page = (new Paginator ())->paginate(
            $movieRepository->createFilteredQueryBuilder($filter),
            $pagination,
        );

        return $this->json($page, Response::HTTP_OK, [], ['groups' => ['paginate', 'movie:read']]);
    }

    #[Route('/{id}', name: 'app_movies_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Movie $movie): JsonResponse
    {
        return $this->json($movie, Response::HTTP_OK, [], ['groups' => ['movie:read']]);
    }

    #[Route('', name: 'app_movies_create', methods: ['POST'])]
    public function create(
        EntityManagerInterface $entityManager,
        #[MapRequestPayload(
            serializationContext: ['groups' => ['movie:write']],
            validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
        )]
        Movie $movie,
    ): JsonResponse {
        $entityManager->persist($movie);
        $entityManager->flush();

        return $this->json(
            $movie,
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('app_movies_show', ['id' => $movie->getId()])],
            ['groups' => ['movie:read']],
        );
    }

    #[Route('/{id}', name: 'app_movies_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(
        Movie $movie,
        EntityManagerInterface $entityManager,
        #[MapRequestPayload(
            serializationContext: ['groups' => ['movie:update']],
            validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
        )]
        MovieUpdateDTO $updateDTO,
    ): JsonResponse {
        $updateDTO->updateEntity($movie);
        $entityManager->flush();

        return $this->json($movie, Response::HTTP_OK, [], ['groups' => ['movie:read']]);
    }

    #[Route('/{id}', name: 'app_movies_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Movie $movie, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($movie);
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
