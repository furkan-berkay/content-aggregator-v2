<?php

namespace App\Controller;

use App\Service\ProviderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;


class ContentController extends AbstractController
{
    #[Route("/api/contents", name: "api_contents", methods: ["GET", "POST"])]
    public function contents(Request $request, ProviderService $providerService, RateLimiterFactory $contentsSearchLimiter): JsonResponse
    {
        $limiter = $contentsSearchLimiter->create($request->getClientIp());
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();
            return $this->json([
                "status"      => "error",
                "message"     => "Too many requests. Please wait.",
                "retry_after" => $retryAfter
            ], 429);
        }


        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $draw   = (int) ($data["draw"] ?? 1);
        $start  = (int) ($data["start"] ?? 0);
        $lengthRaw = (int) ($data["length"] ?? 10);
        $length = $lengthRaw === -1 ? -1 : max(0, $lengthRaw);

        $type    = $data["type"] ?? null;
        $keyword = $data["keyword"] ?? null;

        $columns = ["title", "type", "score", "views"];
        $orderColumn = "score";
        $orderDir    = "DESC";

        if (isset($data["order"][0])) {
            $order       = $data["order"][0];
            $orderColumn = $columns[$order["column"] ?? 2] ?? "score";
            $orderDir    = strtoupper($order["dir"] ?? "DESC");
        }
        elseif (isset($data["orderColumn"])) {
            $orderColumn = in_array($data["orderColumn"], $columns) ? $data["orderColumn"] : "score";
            $orderDir    = strtoupper($data["orderDir"] ?? "DESC");
        }

        $contents        = $providerService->searchContentsCached($type, $keyword, $start, $length, $orderColumn, $orderDir);
        $recordsTotal    = $providerService->countContentsCached(null, null);
        $recordsFiltered = $providerService->countContentsCached($type, $keyword);


        return $this->json([
            "draw"            => $draw,
            "recordsTotal"    => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data"            => $contents,
        ]);
    }

}
