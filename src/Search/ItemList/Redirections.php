<?php

namespace LgtToolkit\Search\ItemList;

use ClassKit\Search\ItemList\ListTrait;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Package\LgtToolkit\Entity\Redirection;
use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Core\Application\{ApplicationAwareInterface, ApplicationAwareTrait};

class Redirections extends ItemList implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;
    use ListTrait;
    use PrefetchesEntities;

    protected $autoSortColumns = [
        'r.sourcePath',
        'r.statusCode',
        'r.date_created',
    ];

    protected function createPaginationObject()
    {
        $adapter = new DoctrineDbalAdapter(
            $this->deliverQueryObject(),
            function (\Doctrine\DBAL\Query\QueryBuilder $query) {
                $query
                    ->resetQueryParts(['groupBy', 'orderBy'])
                    ->select('count(distinct r.id)')
                    ->setMaxResults(1);
            },
        );

        return new Pagination($this, $adapter);
    }

    public function createQuery()
    {
        $this->query->select('r.id')
            ->from('LgtRedirections', 'r')
        ;
    }

    public function filterBySourcePath(?string $sourcePath = '')
    {
        $sourcePath = (string) $sourcePath;
        if ($sourcePath !== '') {
            $this->filterBy('r.sourcePath', mb_strtolower($sourcePath), 'like');
        }
    }

    public function filterByStatusCode(?int $statusCode = null)
    {
        if (in_array((int) $statusCode, Redirection::STATUS_CODES, true)) {
            $this->filterBy('r.statusCode', (int) $statusCode);
        }
    }

    public function getTotalResults()
    {
        $query = $this->deliverQueryObject();
        $query
            ->resetQueryParts(['groupBy', 'orderBy'])
            ->select('count(distinct r.id)')
            ->setMaxResults(1);

        return (int) $query->execute()->fetchOne();
    }

    public function executeGetResults()
    {
        return $this->prefetchEntities(Redirection::class, parent::executeGetResults());
    }

    public function getResult($queryRow)
    {
        return $this->findEntity(Redirection::class, $queryRow['id']);
    }
}
