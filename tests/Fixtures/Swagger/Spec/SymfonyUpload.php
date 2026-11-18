<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec;

use OpenApi\Spec as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[OA\Schema]
final class SymfonyUpload
{
    #[OA\Property]
    public UploadedFile $file;
}
