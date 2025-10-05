<?php

namespace App\Entity;

use App\Repository\ContentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContentRepository::class)]
class Content
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Provider ile ilişki (ManyToOne, ters taraf Provider::contents)
    #[ORM\ManyToOne(inversedBy: 'contents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Provider $provider = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $views = null;

    #[ORM\Column(nullable: true)]
    private ?int $likes = null;

    #[ORM\Column(nullable: true)]
    private ?int $reactions = null;

    #[ORM\Column(nullable: true)]
    private ?int $comments = null;

    #[ORM\Column(nullable: true)]
    private ?int $readingTime = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $tags = null;

    #[ORM\Column(nullable: true)]
    private ?float $score = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 50)]
    private ?string $providerItemId = null;


    public function getProviderItemId(): ?string
    {
        return $this->providerItemId;
    }

    public function setProviderItemId(string $providerItemId): static
    {
        $this->providerItemId = $providerItemId;
        return $this;
    }

    // -----------------------------
    // Getters / Setters
    // -----------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(?int $views): static
    {
        $this->views = $views;
        return $this;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(?int $likes): static
    {
        $this->likes = $likes;
        return $this;
    }

    public function getReactions(): ?int
    {
        return $this->reactions;
    }

    public function setReactions(?int $reactions): static
    {
        $this->reactions = $reactions;
        return $this;
    }

    public function getComments(): ?int
    {
        return $this->comments;
    }

    public function setComments(?int $comments): static
    {
        $this->comments = $comments;
        return $this;
    }

    public function getReadingTime(): ?int
    {
        return $this->readingTime;
    }

    public function setReadingTime(?int $readingTime): static
    {
        $this->readingTime = $readingTime;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags ? json_encode($tags, JSON_UNESCAPED_UNICODE) : null;
        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Skor Hesaplama
    public function calculateScore(): float
    {
        $baseScore        = 0.0;
        $typeMultiplier   = 1.0;
        $recencyScore     = 0.0;
        $interactionScore = 0.0;

        $now = new \DateTimeImmutable();
        $publishedAt = $this->getPublishedAt() ?? $now;

        // Temel Puan
        if ($this->getType() === "video") {
            $baseScore = ($this->getViews() ?? 0) / 1000 + ($this->getLikes() ?? 0) / 100;
            $typeMultiplier = 1.5;
            $interactionScore = ($this->getViews() ?? 1) > 0
                ? ($this->getLikes() ?? 0) / ($this->getViews() ?? 1) * 10
                : 0;
        }
        else { // article
            $baseScore = ($this->getReadingTime() ?? 0) + ($this->getReactions() ?? 0) / 50;
            $interactionScore = ($this->getReadingTime() ?? 1) > 0
                ? ($this->getReactions() ?? 0) / ($this->getReadingTime() ?? 1) * 5
                : 0;
        }

        // Güncellik puanı
        $interval = $publishedAt->diff($now);
        $days = (int) $interval->format("%a");

        if ($days <= 7) {
            $recencyScore = 5;
        }
        elseif ($days <= 30) {
            $recencyScore = 3;
        }
        elseif ($days <= 90) {
            $recencyScore = 1;
        }

        $finalScore = ($baseScore * $typeMultiplier) + $recencyScore + $interactionScore;
        $this->setScore($finalScore);

        return $finalScore;
    }
}
