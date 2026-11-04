<?php

namespace App\Tests\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class MoviesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createMovie(string $title, int $year, string $description = 'A description'): Movie
    {
        $movie = (new Movie())->setTitle($title)->setDescription($description)->setYear($year);
        $this->entityManager->persist($movie);
        $this->entityManager->flush();

        return $movie;
    }

    private function requestJson(string $method, string $uri, ?array $body = null): array
    {
        $this->client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: null === $body ? null : json_encode($body),
        );

        return json_decode($this->client->getResponse()->getContent() ?: 'null', true) ?? [];
    }

    private function listTitles(string $query = ''): array
    {
        $data = $this->requestJson('GET', '/api/movies'.$query);
        self::assertResponseIsSuccessful();

        return array_column($data['items'], 'title');
    }

    public function testHomeReturnsWelcomeResponse(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertSame(
            [
                'message' => 'Bienvenue sur l’API de gestion des films.',
                'movies_endpoint' => '/api/movies',
            ],
            json_decode($this->client->getResponse()->getContent(), true),
        );
    }

    public function testApiRootRedirectsToMoviesList(): void
    {
        $this->client->request('GET', '/api');

        self::assertResponseRedirects('/api/movies');
    }

    public function testMoviesShortcutRedirectsToMoviesList(): void
    {
        $this->client->request('GET', '/movies');

        self::assertResponseRedirects('/api/movies');
    }

    public function testListWithoutMovies(): void
    {
        $data = $this->requestJson('GET', '/api/movies');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(0, $data['total']);
        self::assertSame([], $data['items']);
    }

    public function testListReturnsOnlyReadableFields(): void
    {
        $this->createMovie('My movie', 2026, 'Nice');

        $data = $this->requestJson('GET', '/api/movies');

        self::assertSame(1, $data['total']);
        self::assertSame(1, $data['lastPage']);
        self::assertSame(
            ['id', 'title', 'description', 'year'],
            array_keys($data['items'][0]),
        );
        self::assertSame('My movie', $data['items'][0]['title']);
    }

    public function testListFilters(): void
    {
        $this->createMovie('My first movie', 2026);
        $this->createMovie('Another one', 2026);
        $this->createMovie('MY OLD MOVIE', 1999);

        self::assertCount(3, $this->listTitles());
        // contains, case insensitive
        self::assertSame(['My first movie', 'MY OLD MOVIE'], $this->listTitles('?title=my'));
        self::assertSame(['My first movie', 'Another one'], $this->listTitles('?year=2026'));
        self::assertSame(['My first movie'], $this->listTitles('?title=my&year=2026'));
        self::assertSame([], $this->listTitles('?title=unknown'));
    }

    public function testListPagination(): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $i => $title) {
            $this->createMovie('Movie '.$title, 2000 + $i);
        }

        $data = $this->requestJson('GET', '/api/movies?page=2&limit=2');
        self::assertSame(5, $data['total']);
        self::assertSame(3, $data['lastPage']);
        self::assertSame(['Movie C', 'Movie D'], array_column($data['items'], 'title'));

        self::assertSame(['Movie E'], $this->listTitles('?page=3&limit=2'));
        self::assertSame([], $this->listTitles('?page=4&limit=2'));
    }

    public function testListPaginationWithFilter(): void
    {
        $this->createMovie('Movie one', 2001);
        $this->createMovie('Movie two', 2002);
        $this->createMovie('Other', 2003);

        $data = $this->requestJson('GET', '/api/movies?title=o&page=2&limit=1');
        self::assertSame(3, $data['total']);
        self::assertSame(['Movie two'], array_column($data['items'], 'title'));
    }

    public function testListWithInvalidPagination(): void
    {
        $this->requestJson('GET', '/api/movies?page=0');
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->requestJson('GET', '/api/movies?limit=1000');
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->requestJson('GET', '/api/movies?year=abc');
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testShow(): void
    {
        $movie = $this->createMovie('My movie', 2026, 'Nice');

        $data = $this->requestJson('GET', '/api/movies/'.$movie->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(
            ['id' => $movie->getId(), 'title' => 'My movie', 'description' => 'Nice', 'year' => 2026],
            $data,
        );
    }

    public function testShowUnknownMovie(): void
    {
        $this->requestJson('GET', '/api/movies/9999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreate(): void
    {
        $data = $this->requestJson('POST', '/api/movies', [
            'title' => 'New movie',
            'description' => 'Brand new',
            'year' => 2026,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame('New movie', $data['title']);
        self::assertSame(2026, $data['year']);
        self::assertResponseHeaderSame('Location', '/api/movies/'.$data['id']);
        self::assertNotNull($this->entityManager->getRepository(Movie::class)->find($data['id']));
    }

    public function testCreateIgnoresId(): void
    {
        $data = $this->requestJson('POST', '/api/movies', [
            'id' => 42,
            'title' => 'New movie',
            'description' => 'Brand new',
            'year' => 2026,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertNotSame(42, $data['id']);
    }

    #[DataProvider('invalidPayloads')]
    public function testCreateWithInvalidPayload(array $payload): void
    {
        $this->requestJson('POST', '/api/movies', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame(0, $this->entityManager->getRepository(Movie::class)->count());
    }

    public static function invalidPayloads(): iterable
    {
        yield 'empty body' => [[]];
        yield 'missing year' => [['title' => 'T', 'description' => 'D']];
        yield 'blank title' => [['title' => '', 'description' => 'D', 'year' => 2026]];
        yield 'year is not a number' => [['title' => 'T', 'description' => 'D', 'year' => 'abc']];
        yield 'year out of range' => [['title' => 'T', 'description' => 'D', 'year' => 1500]];
        yield 'title too long' => [['title' => str_repeat('a', 256), 'description' => 'D', 'year' => 2026]];
    }

    public function testPartialUpdate(): void
    {
        $movie = $this->createMovie('Old title', 2000, 'Old description');

        $data = $this->requestJson('PATCH', '/api/movies/'.$movie->getId(), ['title' => 'New title']);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame('New title', $data['title']);
        self::assertSame('Old description', $data['description']);
        self::assertSame(2000, $data['year']);

        $this->entityManager->clear();
        self::assertSame('New title', $this->entityManager->getRepository(Movie::class)->find($movie->getId())->getTitle());
    }

    public function testUpdateWithInvalidPayload(): void
    {
        $movie = $this->createMovie('Old title', 2000);

        $this->requestJson('PATCH', '/api/movies/'.$movie->getId(), ['year' => 1500]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->requestJson('PATCH', '/api/movies/'.$movie->getId(), ['title' => '']);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateUnknownMovie(): void
    {
        $this->requestJson('PATCH', '/api/movies/9999', ['title' => 'Nope']);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDelete(): void
    {
        $movie = $this->createMovie('To delete', 2000);
        $id = $movie->getId();

        $this->requestJson('DELETE', '/api/movies/'.$id);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->requestJson('GET', '/api/movies/'.$id);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteUnknownMovie(): void
    {
        $this->requestJson('DELETE', '/api/movies/9999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // public function testIndex(): void
    // {
    //     $client = static::createClient();
    //     $client->request('GET', '/movies');

    //     self::assertResponseIsSuccessful();
    // }
}
