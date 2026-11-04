<?php

namespace App\Model;

use App\Entity\Movie;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class MovieUpdateDTO
{
    public function __construct(
        #[Groups(['movie:update'])]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public ?string $title = null,

        #[Groups(['movie:update'])]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public ?string $description = null,

        #[Groups(['movie:update'])]
        #[Assert\Range(min: 1888, max: 2100)]
        public ?int $year = null,
    ) {}

    public function updateEntity(Movie $movie): void
    {
        if (null !== $this->title) {
            $movie->setTitle($this->title);
        }

        if (null !== $this->description) {
            $movie->setDescription($this->description);
        }

        if (null !== $this->year) {
            $movie->setYear($this->year);
        }
    }
}
