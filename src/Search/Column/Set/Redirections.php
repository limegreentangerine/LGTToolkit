<?php

namespace LgtToolkit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class Redirections extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column(
            'r.sourcePath',
            t('Old URL'),
            'getSourcePath',
            true,
        ));

        $this->addColumn(new Column(
            'r.statusCode',
            t('Type'),
            'getStatusCode',
            true,
        ));

        $this->addColumn(new Column(
            'r.date_created',
            t('Added'),
            'getDateCreatedString',
            true,
        ));

        $defaultSortColumn = $this->getColumnByKey('r.sourcePath');
        $this->setDefaultSortColumn($defaultSortColumn, 'asc');
    }
}
