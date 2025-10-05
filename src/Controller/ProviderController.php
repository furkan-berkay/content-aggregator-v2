<?php

namespace App\Controller;

use App\Entity\Provider;
use App\Repository\ProviderRepository;
use App\Repository\ContentRepository;
use App\Service\ProviderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Cache\CacheInterface;

class ProviderController extends AbstractController
{
    private RateLimiterFactory $providerLimiter;
    private CacheInterface $cache;

    public function __construct(RateLimiterFactory $providerLimiter, CacheInterface $cache)
    {
        $this->providerLimiter = $providerLimiter;
        $this->cache = $cache;
    }

    private function checkRateLimit(Request $request): ?JsonResponse
    {
        $limiter = $this->providerLimiter->create($request->getClientIp());
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();
            return $this->json([
                "status"      => "error",
                "message"     => "Too many requests. Please wait.",
                "retry_after" => $retryAfter
            ], 429);
        }

        return null;
    }

    #[Route("/api/providers", name: "api_providers_list", methods: ["GET"])]
    public function list(Request $request, ProviderRepository $repo): JsonResponse
    {
        if ($response = $this->checkRateLimit($request)) {
            return $response;
        }

        $providers = $repo->findAll();
        $data = [];

        foreach ($providers as $p) {
            $data[] = [
                "id"        => $p->getId(),
                "name"      => $p->getName(),
                "url"       => $p->getUrl(),
                "format"    => $p->getFormat(),
                "active"    => $p->isActive(),
                "createdAt" => $p->getCreatedAt()?->format("Y-m-d H:i:s")
            ];
        }

        return $this->json($data);
    }

    #[Route("/api/providers", name: "api_providers_add", methods: ["POST"])]
    public function add(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($response = $this->checkRateLimit($request)) {
            return $response;
        }

        $provider = new Provider();
        $provider->setName($request->get("name"));
        $provider->setFormat($request->get("format"));
        $provider->setUrl($request->get("url"));
        $provider->setActive(false);
        $provider->setCreatedAt(new \DateTimeImmutable());
        $provider->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($provider);
        $em->flush();

        $this->cache->clear();

        return $this->json([
            "status" => "ok"
        ], 201);
    }

    #[Route("/api/providers/{id}/soft-delete", name: "api_providers_soft_delete", methods: ["POST"])]
    public function softDelete(
        Request $request,
        int $id,
        ProviderRepository $providerRepo,
        ContentRepository $contentRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($response = $this->checkRateLimit($request)) {
            return $response;
        }

        $provider = $providerRepo->find($id);
        if (!$provider) {
            return $this->json([
                "message" => "Provider not found"
            ], 404);
        }

        $provider->setActive(false);
        $provider->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($provider);

        $contents = $contentRepo->findBy(["provider" => $provider]);
        foreach ($contents as $content) {
            $em->remove($content);
        }

        $em->flush();

        $this->cache->clear();

        return $this->json([
            "status"      => "soft-deleted",
            "provider_id" => $id
        ]);
    }

    #[Route("/api/providers/{id}", name: "api_providers_delete", methods: ["DELETE"])]
    public function delete(
        Request $request,
        int $id,
        ProviderRepository $repo,
        ContentRepository $contentRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($response = $this->checkRateLimit($request)) {
            return $response;
        }

        $provider = $repo->find($id);
        if (!$provider) {
            return $this->json([
                "message" => "Provider not found"
            ], 404);
        }

        $contents = $contentRepo->findBy(["provider" => $provider]);
        foreach ($contents as $content) {
            $em->remove($content);
        }

        $em->remove($provider);
        $em->flush();

        $this->cache->clear();

        return $this->json([
            "status" => "deleted"
        ]);
    }

    #[Route("/api/providers/import", name: "api_providers_import", methods: ["POST"])]
    public function import(Request $request, ProviderService $providerService): JsonResponse
    {
        if ($response = $this->checkRateLimit($request)) {
            return $response;
        }

        $count = $providerService->fetchAndSaveContents();

        $this->cache->clear();

        return $this->json([
            "status"   => "import completed",
            "imported" => $count
        ]);
    }
}
