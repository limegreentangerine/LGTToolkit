<?php

namespace LgtToolkit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class SupportTickets extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column('t.date_created', t('Submitted'), 'getDateCreatedString', true));
        $this->addColumn(new Column('t.localReference', t('Reference'), 'getDisplayReference', true));
        $this->addColumn(new Column('t.status', t('Status'), 'getStatusLabel', true));
        $this->addColumn(new Column('t.category', t('Category'), 'getCategoryLabel', true));
        $this->addColumn(new Column('t.subject', t('Subject'), 'getSubject', true));
        $this->addColumn(new Column('t.email', t('From'), 'getSubmitter', true));

        $this->setDefaultSortColumn($this->getColumnByKey('t.date_created'), 'desc');
    }
}
