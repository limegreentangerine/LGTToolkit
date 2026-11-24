<?php

namespace LgtToolkit\Search\ItemList;

use LgtToolkit\Support\TicketLog;
use ClassKit\Search\ItemList\ListTrait;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Package\LgtToolkit\Entity\SupportTicket;
use Concrete\Core\Application\{ApplicationAwareInterface, ApplicationAwareTrait};

class SupportTickets extends ItemList implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;
    use ListTrait;
    use PrefetchesEntities;

    protected $autoSortColumns = [
        't.date_created',
        't.localReference',
        't.status',
        't.category',
        't.subject',
        't.email',
    ];

    protected function createPaginationObject()
    {
        $adapter = new DoctrineDbalAdapter(
            $this->deliverQueryObject(),
            function (\Doctrine\DBAL\Query\QueryBuilder $query) {
                $query
                    ->resetQueryParts(['groupBy', 'orderBy'])
                    ->select('count(distinct t.id)')
                    ->setMaxResults(1);
            },
        );

        return new Pagination($this, $adapter);
    }

    public function createQuery()
    {
        $this->query->select('t.id')
            ->from(TicketLog::TABLE, 't')
        ;
    }

    /**
     * Matches the references, subject, sender name or email.
     */
    public function filterByKeywords(?string $keywords = '')
    {
        $keywords = trim((string) $keywords);
        if ($keywords === '') {
            return;
        }

        $expr = $this->query->expr();
        $this->query->andWhere($expr->or(
            $expr->like('t.localReference', ':keywords'),
            $expr->like('t.portalReference', ':keywords'),
            $expr->like('t.subject', ':keywords'),
            $expr->like('t.email', ':keywords'),
            $expr->like('t.name', ':keywords'),
        ));
        $this->query->setParameter('keywords', '%' . addcslashes($keywords, '%_\\') . '%');
    }

    public function filterByStatus(?string $status = '')
    {
        if (in_array($status, [TicketLog::STATUS_DELIVERED, TicketLog::STATUS_QUEUED, TicketLog::STATUS_FAILED], true)) {
            $this->filterBy('t.status', $status);
        }
    }

    public function filterByCategory(?string $category = '')
    {
        if ($category !== null && $category !== '') {
            $this->filterBy('t.category', $category);
        }
    }

    public function getTotalResults()
    {
        $query = $this->deliverQueryObject();
        $query
            ->resetQueryParts(['groupBy', 'orderBy'])
            ->select('count(distinct t.id)')
            ->setMaxResults(1);

        return (int) $query->execute()->fetchOne();
    }

    public function executeGetResults()
    {
        return $this->prefetchEntities(SupportTicket::class, parent::executeGetResults());
    }

    public function getResult($queryRow)
    {
        return $this->findEntity(SupportTicket::class, $queryRow['id']);
    }
}
