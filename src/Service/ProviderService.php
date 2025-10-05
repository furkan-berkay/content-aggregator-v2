<?php

namespace App\Service;

use App\Entity\Provider;
use App\Entity\Content;
use App\Repository\ProviderRepository;
use App\Repository\ContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class ProviderService
{
    private ProviderRepository $providerRepository;
    private ContentRepository $contentRepository;
    private EntityManagerInterface $em;
    private CacheInterface $cache;

    public function __construct(
        ProviderRepository $providerRepository,
        ContentRepository $contentRepository,
        EntityManagerInterface $em,
        CacheInterface $cache
    ) {
        $this->providerRepository = $providerRepository;
        $this->contentRepository = $contentRepository;
        $this->em = $em;
        $this->cache = $cache;
    }

    public function fetchAndSaveContents(): int
    {
        $providers = $this->providerRepository->findBy(["active" => 0]);
        $importedCount = 0;

        foreach ($providers as $provider) {
            try {
                $client = $this->getClient($provider);
                $contents = $client->fetchContents();

                if (empty($contents)) {
                    //echo "Provider {$provider->getName()} returned no contents.\n";
                    continue;
                }

                foreach ($contents as $content) {
                    $content->calculateScore(); // → score setle
                    $this->em->persist($content);
                }

                // Tüm içerikler eklendiyse provider aktif et
                $provider->setActive(true);
                $this->em->persist($provider);

                $this->em->flush();
                $importedCount++;

                //echo "Provider {$provider->getName()} imported " . count($contents) . " contents.\n";

            } catch (\Throwable $e) {
                //echo "Error importing provider {$provider->getName()}: " . $e->getMessage() . "\n";
                continue; // diğer providerlara geç
            }
        }

        //echo "Total providers imported: {$importedCount}\n";
        if ($importedCount > 0) {
            $this->cache->clear();
        }
        return $importedCount;
    }

    private function getClient(Provider $provider): ProviderClientInterface
    {
        return match($provider->getFormat()) {
            "json"  => new JsonProviderClient($provider),
            "xml"   => new XmlProviderClient($provider),
            default => throw new \Exception("Unsupported provider format: {$provider->getFormat()}")
        };
    }

    public function searchContents(
        ?string $type,
        ?string $keyword,
        int $start = 0,
        int $length = 10,
        string $orderColumn = "score",
        string $orderDir = "DESC"
    ): array {
        $conn = $this->em->getConnection();

        $allowedColumns = ["score", "views", "likes", "published_at"];
        $orderColumn = in_array($orderColumn, $allowedColumns) ? $orderColumn : "score";
        $orderDir    = strtoupper($orderDir) === "ASC" ? "ASC" : "DESC";

        $sql = "SELECT * FROM content c WHERE 1=1";
        $params = [];

        if ($type) {
            $sql .= " AND c.type = :type";
            $params["type"] = $type;
        }

        if ($keyword) {
            $sql .= " AND (c.title LIKE :keyword OR c.tags LIKE :keyword)";
            $params["keyword"] = "%$keyword%";
        }

        $sql .= " ORDER BY $orderColumn $orderDir LIMIT :start, :length";
        $params["start"]  = $start;
        $params["length"] = $length;

        $stmt = $conn->executeQuery($sql, $params, [
            "type"    => \Doctrine\DBAL\ParameterType::STRING,
            "keyword" => \Doctrine\DBAL\ParameterType::STRING,
            "start"   => \Doctrine\DBAL\ParameterType::INTEGER,
            "length"  => \Doctrine\DBAL\ParameterType::INTEGER,
        ]);

        return $stmt->fetchAllAssociative();
    }

    public function countContents(?string $type, ?string $keyword): int
    {
        $conn = $this->em->getConnection();

        $sql = "SELECT COUNT(*) as cnt FROM content c WHERE 1=1";
        $params = [];

        if ($type) {
            $sql .= " AND c.type = :type";
            $params["type"] = $type;
        }

        if ($keyword) {
            $sql .= " AND (c.title LIKE :keyword OR c.tags LIKE :keyword)";
            $params["keyword"] = "%$keyword%";
            $params["keywordJson"] = json_encode([$keyword], JSON_UNESCAPED_UNICODE);
        }

        $stmt = $conn->executeQuery($sql, $params, [
            "type"        => \Doctrine\DBAL\ParameterType::STRING,
            "keyword"     => \Doctrine\DBAL\ParameterType::STRING,
            "keywordJson" => \Doctrine\DBAL\ParameterType::STRING,
        ]);

        return (int) $stmt->fetchOne();
    }

    public function searchContentsCached(?string $type, ?string $keyword, int $start = 0, int $length = 10, string $orderColumn = "score", string $orderDir = "DESC"): array
    {
        $cacheKey = md5("contents_search_{$type}_{$keyword}_{$start}_{$length}_{$orderColumn}_{$orderDir}");

        return $this->cache->get($cacheKey, function($item) use ($type, $keyword, $start, $length, $orderColumn, $orderDir) {
            $item->expiresAfter(300);
            return $this->searchContents($type, $keyword, $start, $length, $orderColumn, $orderDir);
        });
    }

    public function countContentsCached(?string $type, ?string $keyword): int
    {
        $cacheKey = md5("contents_count_{$type}_{$keyword}");

        return $this->cache->get($cacheKey, function($item) use ($type, $keyword) {
            $item->expiresAfter(300);
            return $this->countContents($type, $keyword);
        });
    }


}
