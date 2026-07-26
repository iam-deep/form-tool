<?php

namespace Deep\FormTool\Contracts;

use DateTimeInterface;

interface PrivateFileUrlResolver
{
    public function resolve(string $path, string $disk, DateTimeInterface $expiresAt): string;
}
