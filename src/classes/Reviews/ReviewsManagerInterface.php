<?php

namespace Reviews;

interface ReviewsManagerInterface
{
    public function getReviewsBySpot(int $spotId): array;
}
