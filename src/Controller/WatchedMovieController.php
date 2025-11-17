<?php

namespace App\Controller;

use App\Entity\WatchedMovie;
use App\Repository\WatchedMovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/watched-movies', name: 'api_watched_movies_')]
final class WatchedMovieController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WatchedMovieRepository $watchedMovieRepository
    ) {
    }

    /**
     * Ajouter un film aux "déjà vus"
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id']) || !isset($data['title']) || !isset($data['director']) || !isset($data['releaseDate'])) {
            return new JsonResponse(
                ['error' => 'Données manquantes. Requis: id, title, director, releaseDate'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $existingMovie = $this->watchedMovieRepository->findOneBy(['idApi' => (string) $data['id']]);
        if ($existingMovie) {
            return new JsonResponse(
                ['error' => 'Ce film est déjà dans la liste des "déjà vus"'],
                Response::HTTP_CONFLICT
            );
        }

        // Créer un nouveau WatchedMovie
        $watchedMovie = new WatchedMovie();
        $watchedMovie->setIdApi((string) $data['id']);
        $watchedMovie->setTitle($data['title']);
        $watchedMovie->setDirector($data['director'] ?? 'Inconnu');
        
        try {
            $releaseDateStr = $data['releaseDate'];
            // Accepter format YYYY ou YYYY-MM-DD
            if (preg_match('/^\d{4}$/', $releaseDateStr)) {
                $releaseDate = new \DateTime($releaseDateStr . '-01-01');
            } else {
                $releaseDate = new \DateTime($releaseDateStr);
            }
            $watchedMovie->setReleaseDate($releaseDate);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Format de date invalide. Utilisez le format YYYY ou YYYY-MM-DD'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->entityManager->persist($watchedMovie);
        $this->entityManager->flush();

        $movie = [
            'id' => $watchedMovie->getIdApi(),
            'idApi' => $watchedMovie->getIdApi(),
            'title' => $watchedMovie->getTitle(),
            'director' => $watchedMovie->getDirector(),
            'releaseDate' => $watchedMovie->getReleaseDate()?->format('Y-m-d') ?? '',
        ];

        $response = new JsonResponse($movie, Response::HTTP_CREATED);
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        
        return $response;
    }

    /**
     * Récupérer la liste de tous les films déjà vus
     * GET /api/watched-movies?sortBy=title&order=asc&title=inception&director=nolan&year=2010
     * 
     * Paramètres de tri :
     * - sortBy : champ par lequel trier (title, director, releaseDate) - par défaut: title
     * - order : ordre de tri (asc, desc) - par défaut: asc
     * 
     * Paramètres de recherche :
     * - title : filtre par titre (recherche partielle, insensible à la casse)
     * - director : filtre par réalisateur (recherche partielle, insensible à la casse)
     * - year : filtre par année de sortie (exacte)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->query->get('sortBy', 'title');
        $order = strtolower($request->query->get('order', 'asc'));

        $titleFilter = $request->query->get('title', '');
        $directorFilter = $request->query->get('director', '');
        $yearFilter = $request->query->get('year', '');

        $allowedSortFields = ['title', 'director', 'releaseDate'];
        if (!in_array($sortBy, $allowedSortFields, true)) {
            return new JsonResponse(
                ['error' => sprintf('Champ de tri invalide. Champs autorisés: %s', implode(', ', $allowedSortFields))],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!in_array($order, ['asc', 'desc'], true)) {
            return new JsonResponse(
                ['error' => 'Ordre de tri invalide. Utilisez "asc" ou "desc"'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $qb = $this->watchedMovieRepository->createQueryBuilder('wm');

        if (!empty($titleFilter)) {
            $qb->andWhere('LOWER(wm.title) LIKE :title')
               ->setParameter('title', '%' . strtolower($titleFilter) . '%');
        }

        if (!empty($directorFilter)) {
            $qb->andWhere('LOWER(wm.director) LIKE :director')
               ->setParameter('director', '%' . strtolower($directorFilter) . '%');
        }

        if (!empty($yearFilter)) {
            $year = (int) $yearFilter;
            $startDate = new \DateTime($year . '-01-01');
            $endDate = new \DateTime($year . '-12-31');
            $qb->andWhere('wm.releaseDate >= :startDate')
               ->andWhere('wm.releaseDate <= :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        $qb->orderBy('wm.' . $sortBy, strtoupper($order));

        $watchedMovies = $qb->getQuery()->getResult();

        $movies = array_map(function (WatchedMovie $watchedMovie) {
            return [
                'id' => $watchedMovie->getIdApi(),
                'idApi' => $watchedMovie->getIdApi(),
                'title' => $watchedMovie->getTitle(),
                'director' => $watchedMovie->getDirector(),
                'releaseDate' => $watchedMovie->getReleaseDate()?->format('Y-m-d') ?? '',
            ];
        }, $watchedMovies);

        return new JsonResponse($movies, Response::HTTP_OK);
    }

    /**
     * Récupérer les détails d'un film déjà vu
     */
    #[Route('/{idApi}', name: 'show', methods: ['GET'])]
    public function show(string $idApi): JsonResponse
    {
        $watchedMovie = $this->watchedMovieRepository->findOneBy(['idApi' => $idApi]);

        if (!$watchedMovie) {
            return new JsonResponse(
                ['error' => 'Film non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        $movie = [
            'id' => $watchedMovie->getIdApi(),
            'idApi' => $watchedMovie->getIdApi(),
            'title' => $watchedMovie->getTitle(),
            'director' => $watchedMovie->getDirector(),
            'releaseDate' => $watchedMovie->getReleaseDate()?->format('Y-m-d') ?? '',
        ];

        return new JsonResponse($movie, Response::HTTP_OK);
    }

    /**
     * Supprimer un film des "déjà vus"
     */
    #[Route('/{idApi}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $idApi): JsonResponse
    {
        $watchedMovie = $this->watchedMovieRepository->findOneBy(['idApi' => $idApi]);

        if (!$watchedMovie) {
            return new JsonResponse(
                ['error' => 'Film non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->entityManager->remove($watchedMovie);
        $this->entityManager->flush();

        return new JsonResponse(
            ['message' => 'Film supprimé avec succès'],
            Response::HTTP_OK
        );
    }
}
