<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\OfferLetterTemplate;
use Illuminate\Pagination\LengthAwarePaginator;

interface OfferLetterTemplateRepositoryInterface
{
    public function findByUuid(string $uuid): ?OfferLetterTemplate;

    public function findActiveByType(string $type): ?OfferLetterTemplate;

    /**
     * @return LengthAwarePaginator<OfferLetterTemplate>
     */
    public function paginateActive(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): OfferLetterTemplate;

    public function update(OfferLetterTemplate $template, array $data): OfferLetterTemplate;

    public function delete(OfferLetterTemplate $template): bool;

    public function getOrCreateDefault(string $type): OfferLetterTemplate;
}
