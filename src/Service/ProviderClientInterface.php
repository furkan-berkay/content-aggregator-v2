<?php

namespace App\Service;

use App\Entity\Content;

interface ProviderClientInterface
{
    /**
     * Fetch contents from the provider
     *
     * @return Content[] Array of Content entities
     */
    public function fetchContents(): array;
}
