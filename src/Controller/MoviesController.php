<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MoviesController extends AbstractController
{
    #[Route('/movies', name: 'app_movies', methods: ['GET', 'POST'])]
    public function index(Request $request): JsonResponse
    {
        $data = [
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MoviesController.php',
        ];
        return $this->json($data);
    }
}
