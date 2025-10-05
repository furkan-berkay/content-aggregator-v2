<?php

namespace App\Service;

use App\Entity\Content;
use App\Entity\Provider;

class JsonProviderClient implements ProviderClientInterface
{
    private Provider $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    public function fetchContents(): array
    {
        $url = $this->provider->getUrl();
        $json = @file_get_contents($url);
        if (!$json) {
            throw new \Exception("JSON URL could not be fetched: {$url}");
        }

        $data = json_decode($json, true);
        if (!$data || !isset($data["contents"])) {
            throw new \Exception("Invalid JSON structure from: {$url}");
        }

        $contents = [];
        foreach ($data["contents"] as $item) {
            $content = new Content();
            $content->setProvider($this->provider);
            $content->setProviderItemId($item["id"]);
            $content->setTitle($item["title"]);
            $content->setType($item["type"]);
            $content->setViews((int)($item["metrics"]["views"] ?? 0));
            $content->setLikes((int)($item["metrics"]["likes"] ?? 0));
            // readingTime için saniye veya dakika farkını hesaplamak gerekiyor
            $duration = $item["metrics"]["duration"] ?? null;
            $content->setReadingTime($this->convertDurationToMinutes($duration));
            $content->setPublishedAt(new \DateTimeImmutable($item["published_at"]));
            $content->setTags($item["tags"] ?? []);
            $content->setCreatedAt(new \DateTimeImmutable());
            $content->setUpdatedAt(new \DateTimeImmutable());

            $contents[] = $content;
        }

        return $contents;
    }

    private function convertDurationToMinutes(?string $duration): ?int
    {
        if (!$duration) return null;
        $parts = explode(":", $duration);
        $minutes = 0;
        $seconds = 0;

        if (count($parts) === 3) {
            $minutes = ((int)$parts[0]) * 60 + ((int)$parts[1]);
            $seconds = (int)$parts[2];
        }
        elseif (count($parts) === 2) {
            $minutes = (int)$parts[0];
            $seconds = (int)$parts[1];
        }

        if ($seconds >= 30) {
            $minutes++;
        }
        return $minutes;
    }
}
