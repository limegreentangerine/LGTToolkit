<?php

namespace LgtToolkit\Redirections;

use Page;
use Request;
use Concrete\Core\Entity\Page\PagePath;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\LgtToolkit\Entity\Redirection;

/**
 * Creates, updates, removes and imports redirections, keeping the destination page's
 * additional PagePath in step with each rule.
 */
class RedirectionService
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Connection $db,
    ) {}

    /**
     * @return array{0: string, 1: Page, 2: int}
     */
    protected function validate(string $source, int $cID, int|string|null $code, ?Redirection $existing): array
    {
        $path = PathNormaliser::normalise($source, $this->getSiteHost());

        if ($path === null) {
            throw new RedirectionException(t('"%s" is not a valid path on this site.', $source));
        }

        if (PathNormaliser::isReserved($path)) {
            throw new RedirectionException(t('"%s" is a reserved system path and cannot be redirected.', $path));
        }

        $status = $this->resolveStatusCode($code);

        $page = Page::getByID($cID, 'ACTIVE');

        if (!$this->isUsableDestination($page)) {
            throw new RedirectionException(t('The destination must be an existing, published page.'));
        }

        if ($path === mb_strtolower((string) $page->getCollectionPath())) {
            throw new RedirectionException(t('"%s" is already the address of the destination page.', $path));
        }

        $rule = $this->findBySourcePath($path);

        if ($rule && (!$existing || $rule->getID() !== $existing->getID())) {
            throw new RedirectionException(t('A redirection from "%s" already exists.', $path));
        }

        $row = $this->db->fetchAssociative('SELECT ppID, cID FROM PagePaths WHERE cPath = ?', [$path]);

        if ($row && (!$existing || (int) $row['ppID'] !== (int) $existing->getPagePathID())) {
            throw new RedirectionException(t('"%s" is already used by a page on this site.', $path));
        }

        return [$path, $page, $status];
    }

    protected function resolveStatusCode(int|string|null $code): int
    {
        if ($code === null || trim((string) $code) === '') {
            return Redirection::DEFAULT_STATUS;
        }

        if (!ctype_digit(trim((string) $code)) || !in_array((int) $code, Redirection::STATUS_CODES, true)) {
            throw new RedirectionException(t('The status code must be 301 or 302.'));
        }

        return (int) $code;
    }

    protected function isUsableDestination(mixed $page): bool
    {
        return is_object($page)
            && !$page->isError()
            && !$page->isInTrash()
            && !$page->isAdminArea()
            && !$page->isSystemPage()
            && (string) $page->getCollectionPointerExternalLink() === '';
    }

    protected function addPagePath(Page $page, string $path): ?int
    {
        $pagePath = $page->addAdditionalPagePath($path);

        return $pagePath->getPagePathID() ? (int) $pagePath->getPagePathID() : null;
    }

    /**
     * Removes a PagePath we created; canonical paths are never touched.
     */
    protected function removePagePath(?int $ppID): void
    {
        if (!$ppID) {
            return;
        }

        $pagePath = $this->em->find(PagePath::class, $ppID);

        if ($pagePath && !$pagePath->isPagePathCanonical()) {
            $this->em->remove($pagePath);
            $this->em->flush();
        }
    }

    protected function getSiteHost(): ?string
    {
        return Request::getInstance()->getHost() ?: null;
    }

    public function getByID(string $id): ?Redirection
    {
        return $this->em->find(Redirection::class, $id);
    }

    public function findBySourcePath(string $path): ?Redirection
    {
        return $this->em->getRepository(Redirection::class)->findOneBy(['sourcePath' => $path]);
    }

    /**
     * The live destination page for a rule, or null when it is gone, trashed, or not routable.
     */
    public function resolveDestination(Redirection $redirection): ?Page
    {
        $page = Page::getByID($redirection->getPageID(), 'ACTIVE');

        return $this->isUsableDestination($page) ? $page : null;
    }

    public function create(string $source, int $cID, int|string|null $code = null): Redirection
    {
        [$path, $page, $status] = $this->validate($source, $cID, $code, null);

        return $this->em->wrapInTransaction(function () use ($path, $page, $status) {
            $redirection = (new Redirection())
                ->setSourcePath($path)
                ->setPageID((int) $page->getCollectionID())
                ->setStatusCode($status)
                ->setPagePathID($this->addPagePath($page, $path));

            $this->em->persist($redirection);

            return $redirection;
        });
    }

    public function update(string $id, string $source, int $cID, int|string|null $code = null): Redirection
    {
        $redirection = $this->getByID($id);

        if (!$redirection) {
            throw new RedirectionException(t('That redirection no longer exists.'));
        }

        [$path, $page, $status] = $this->validate($source, $cID, $code, $redirection);

        return $this->em->wrapInTransaction(function () use ($redirection, $path, $page, $status) {
            $moved = $path !== $redirection->getSourcePath()
                || (int) $page->getCollectionID() !== $redirection->getPageID();

            if ($moved) {
                $this->removePagePath($redirection->getPagePathID());
                $redirection
                    ->setSourcePath($path)
                    ->setPageID((int) $page->getCollectionID())
                    ->setPagePathID($this->addPagePath($page, $path));
            }

            $redirection->setStatusCode($status);

            return $redirection;
        });
    }

    public function delete(string $id): bool
    {
        $redirection = $this->getByID($id);

        if (!$redirection) {
            return false;
        }

        $this->em->wrapInTransaction(function () use ($redirection) {
            $this->removePagePath($redirection->getPagePathID());
            $this->em->remove($redirection);
        });

        return true;
    }

    /**
     * Imports CSV rows (source, destination path, optional code), skipping and reporting bad ones.
     *
     * @return array{created: int, errors: array<int, array{line: int, source: string, message: string}>}
     */
    public function importCsv(string $content): array
    {
        $parsed = CsvParser::parse($content);
        $created = 0;
        $errors = [];
        $seen = [];

        foreach ($parsed['errors'] as $message) {
            $errors[] = ['line' => 0, 'source' => '', 'message' => $message];
        }

        foreach ($parsed['rows'] as $row) {
            try {
                $path = PathNormaliser::normalise($row['source'], $this->getSiteHost());

                if ($path !== null && isset($seen[$path])) {
                    throw new RedirectionException(t('Duplicate of line %s in this file.', $seen[$path]));
                }

                $page = $this->findDestinationPage($row['destination']);

                if (!$page) {
                    throw new RedirectionException(t('Destination "%s" is not a page on this site.', $row['destination']));
                }

                $this->create($row['source'], (int) $page->getCollectionID(), $row['code']);

                $seen[$path] = $row['line'];
                $created++;
            } catch (RedirectionException $e) {
                $errors[] = ['line' => $row['line'], 'source' => $row['source'], 'message' => $e->getMessage()];
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Finds a destination page from a site path or full URL.
     */
    public function findDestinationPage(string $destination): ?Page
    {
        $path = PathNormaliser::normalise($destination, $this->getSiteHost());

        if ($path === null) {
            return null;
        }

        $page = Page::getByPath($path, 'ACTIVE');

        return $this->isUsableDestination($page) ? $page : null;
    }
}
