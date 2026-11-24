<?php

namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\Support;

use Concrete\Core\User\User;
use LgtToolkit\Support\TicketLog;
use LgtToolkit\Support\TicketCategory;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Package\LgtToolkit\Entity\SupportTicket;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Package\LgtToolkit\Controller\Search\SupportTickets as SearchController;

/**
 * Every ticket submitted through the site. Super user only: checked here for every action,
 * so page permissions can't widen it.
 */
class Tickets extends DashboardPageController
{
    public function on_start()
    {
        if (!$this->app->make(User::class)->isSuperUser()) {
            return $this->app->make(ResponseFactoryInterface::class)->forbidden($this->request->getUri());
        }

        parent::on_start();
    }

    public function view()
    {
        $search = $this->app->make(SearchController::class);
        $search->search();

        $result = $search->getSearchResultObject();
        $sizes = $search->getAllowedPaginationSizes();
        $request = $search->getStickyRequest()->getSearchRequest();

        $categories = [];
        foreach (TicketCategory::cases() as $category) {
            $categories[$category->value] = $category->label();
        }

        $this->set('result', $result);
        $this->set('items', $result->getItems());
        $this->set('pagination', $result->getPaginationHTML());
        $this->set('numResultsOptions', array_combine($sizes, $sizes));
        $this->set('numResults', $request['num_results'] ?? $search->getDefaultPaginationSize());
        $this->set('filterKeywords', (string) ($request['keywords'] ?? ''));
        $this->set('filterStatus', (string) ($request['status'] ?? ''));
        $this->set('filterCategory', (string) ($request['category'] ?? ''));
        $this->set('statusOptions', [
            TicketLog::STATUS_DELIVERED => t('Delivered'),
            TicketLog::STATUS_QUEUED => t('Queued'),
            TicketLog::STATUS_FAILED => t('Failed'),
        ]);
        $this->set('categoryOptions', $categories);
    }

    public function detail($id = null)
    {
        $ticket = $id ? $this->app->make(EntityManagerInterface::class)->find(SupportTicket::class, (string) $id) : null;
        if (!$ticket) {
            $this->flash('message', t('That ticket could not be found.'));

            return $this->redirect('/dashboard/support/tickets');
        }

        $this->set('ticket', $ticket);
        $this->set('dateHelper', $this->app->make('date'));
    }
}
