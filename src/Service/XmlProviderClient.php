<?php

namespace App\Service;

use App\Entity\Content;
use App\Entity\Provider;

class XmlProviderClient implements ProviderClientInterface
{
    private Provider $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    public function fetchContents(): array
    {
        $url = $this->provider->getUrl();
        $xmlString = @file_get_contents($url);
        if (!$xmlString) {
            throw new \Exception("XML URL could not be fetched: {$url}");
        }

        $xml = simplexml_load_string($xmlString);
        if (!$xml) {
            throw new \Exception("Invalid XML structure from: {$url}");
        }

        $contents = [];

        foreach ($xml->items->item as $item) {
            $content = new Content();
            $content->setProvider($this->provider);
            $content->setProviderItemId((string)$item->id);
            $content->setTitle((string)$item->headline);
            $content->setType((string)$item->type);

            if ($content->getType() === "video") {
                $content->setViews((int)($item->stats->views ?? 0));
                $content->setLikes((int)($item->stats->likes ?? 0));
                $content->setReadingTime($this->convertDurationToMinutes((string)($item->stats->duration ?? null)));
            }
            else {
                $content->setReadingTime((int)($item->stats->reading_time ?? 0));
                $content->setReactions((int)($item->stats->reactions ?? 0));
                $content->setComments((int)($item->stats->comments ?? 0));
            }

            $content->setPublishedAt(new \DateTimeImmutable((string)$item->publication_date));

            $tags = [];
            if (isset($item->categories->category)) {
                foreach ($item->categories->category as $cat) {
                    $tags[] = (string)$cat;
                }
            }
            $content->setTags($tags);

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
