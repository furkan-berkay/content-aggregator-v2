<?php

namespace App\Tests\Controller;

use App\Controller\ContentController;
use App\Service\ProviderService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * Unit tests for ContentController::contents()
 */
class ContentControllerTest extends TestCase
{
    public function testContentsReturnsExpectedJson(): void
    {
        // --- Fake Limiter that always allows requests ---
        $limiter = new class() implements LimiterInterface {
            public function consume(int $tokens = 1): RateLimit
            {
                return new RateLimit(1, new \DateTimeImmutable('+1 minute'), true, 1);
            }
        };

        // --- Fake RateLimiterFactory (since it's final) ---
        $fakeRateLimiterFactory = new class($limiter) {
            private LimiterInterface $limiter;
            public function __construct(LimiterInterface $limiter) { $this->limiter = $limiter; }
            public function create(string $key): LimiterInterface { return $this->limiter; }
        };

        // --- Mock ProviderService ---
        $providerService = $this->createMock(ProviderService::class);

        $mockContents = [
            [
                'id' => 1,
                'title' => 'Programming 101',
                'type' => 'video',
                'score' => 95,
                'views' => 1200,
            ],
        ];

        $providerService->expects($this->once())
            ->method('searchContentsCached')
            ->with('video', 'programm', 0, 1, 'views', 'ASC')
            ->willReturn($mockContents);

        $providerService->expects($this->exactly(2))
            ->method('countContentsCached')
            ->willReturnOnConsecutiveCalls(100, 1);

        // --- JSON request ---
        $jsonData = json_encode([
            'type' => 'video',
            'keyword' => 'programm',
            'start' => 0,
            'length' => 1,
            'orderColumn' => 'views',
            'orderDir' => 'ASC'
        ]);
        $request = new Request(content: $jsonData);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        // --- Controller ---
        $controller = new ContentController();

        // --- Run ---
        $response = $controller->contents($request, $providerService, $fakeRateLimiterFactory);

        // --- Verify ---
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(1, $data['draw']);
        $this->assertEquals(100, $data['recordsTotal']);
        $this->assertEquals(1, $data['recordsFiltered']);
        $this->assertEquals($mockContents, $data['data']);
    }

    public function testRateLimitExceededReturns429(): void
    {
        // --- Fake Limiter that rejects requests ---
        $limiter = new class() implements LimiterInterface {
            public function consume(int $tokens = 1): RateLimit
            {
                return new RateLimit(0, new \DateTimeImmutable('+5 seconds'), false, 0);
            }
        };

        // --- Fake RateLimiterFactory ---
        $fakeRateLimiterFactory = new class($limiter) {
            private $limiter;
            public function __construct($limiter) { $this->limiter = $limiter; }
            public function create(string $key): LimiterInterface { return $this->limiter; }
        };

        $providerService = $this->createMock(ProviderService::class);
        $providerService->expects($this->never())->method('searchContentsCached');

        $request = new Request(content: '{}');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $controller = new ContentController();
        $response = $controller->contents($request, $providerService, $fakeRateLimiterFactory);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(429, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('retry_after', $data);
    }
}
