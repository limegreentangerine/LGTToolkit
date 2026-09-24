<?php

namespace LgtToolkit\Slideshow\Options;

use Core;
use Concrete\Core\Entity\Package;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Error\UserMessageException;

final readonly class SlideTemplate
{
    protected Package $pkg;

    public function __construct(
        public string $path,
        public string $pkgHandle,
    ) {
        $this->pkg = Core::make(PackageService::class)->getByHandle($pkgHandle);
        if (!$this->pkg) {
            throw new UserMessageException(t('Package with handle `%s` does not exist', $pkgHandle));
        }
    }

    public static function fromArray(?array $data): ?self
    {
        if (!$data) {
            return null;
        }

        return new self(
            path: $data['path'] ?? null,
            pkgHandle: $data['pkgHandle'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'pkgHandle' => $this->pkgHandle,
        ];
    }

    /**
     * Get the Package object
     */
    public function getPackage(): ?Package
    {
        return $this->pkg;
    }

    /**
     * Get the Package Handle
     */
    public function getPackageHandle(): ?string
    {
        return $this->getPackage()->getPackageHandle();
    }
}
