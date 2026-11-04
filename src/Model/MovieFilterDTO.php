<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class MovieFilterDTO
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $title = null,

        #[Assert\Range(min: 1888, max: 2100)]
        public ?int $year = null,
    ) {}
}
