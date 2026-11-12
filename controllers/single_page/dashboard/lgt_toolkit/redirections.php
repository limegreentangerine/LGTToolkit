<?php

namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use Symfony\Component\HttpFoundation\Response;
use Concrete\Package\LgtToolkit\Entity\Redirection;
use Concrete\Core\Page\Controller\DashboardPageController;
use LgtToolkit\Redirections\{CsvParser, RedirectionException, RedirectionService};
use Concrete\Package\LgtToolkit\Controller\Search\Redirections as SearchController;

class Redirections extends DashboardPageController
{
    public const MAX_UPLOAD_BYTES = 2097152;

    protected $helpers = [
        'form',
        'concrete/ui',
        'form/page_selector',
    ];

    protected RedirectionService $service;

    protected bool $isEditing = false;

    protected function validateToken(string $action): bool
    {
        if (!$this->request->isPost()) {
            return false;
        }

        if (!$this->token->validate($action)) {
            $this->error->add($this->token->getErrorMessage());

            return false;
        }

        return true;
    }

    protected function setEditing(mixed $id): bool
    {
        $redirection = $this->service->getByID((string) $id);

        $this->isEditing = $redirection !== null;
        $this->set('editing', $redirection);

        return $redirection !== null;
    }

    protected function redirectToList(): void
    {
        $this->redirect('/dashboard/lgt_toolkit/redirections');
    }

    protected function loadList(): void
    {
        $search = $this->app->make(SearchController::class);
        $search->search();

        $result = $search->getSearchResultObject();
        $sizes = $search->getAllowedPaginationSizes();
        $request = $search->getStickyRequest()->getSearchRequest();

        $this->set('result', $result);
        $this->set('items', $result->getItems());
        $this->set('pagination', $result->getPaginationHTML());
        $this->set('numResultsOptions', array_combine($sizes, $sizes));
        $this->set('numResults', $request['num_results'] ?? $search->getDefaultPaginationSize());
        $this->set('filterSource', (string) ($request['source'] ?? ''));
        $this->set('filterCode', (string) ($request['code'] ?? ''));
    }

    public function on_start()
    {
        parent::on_start();

        $this->service = $this->app->make(RedirectionService::class);
    }

    public function on_before_render()
    {
        parent::on_before_render();

        $this->set('token', $this->token);
        $this->set('statusOptions', $this->getStatusOptions());
        $this->set('defaultStatus', Redirection::DEFAULT_STATUS);
        $this->set('importReport', $this->app->make('session')->getFlashBag()->get('lgt_redirections_import')[0] ?? null);

        if (!$this->isEditing) {
            $this->loadList();
        }
    }

    public function view() {}

    public function add()
    {
        if ($this->validateToken('lgt_redirections_add')) {
            try {
                $this->service->create(
                    (string) $this->post('source'),
                    (int) $this->post('destination'),
                    $this->post('code'),
                );

                $this->flash('success', t('Redirection added.'));
                $this->redirectToList();
            } catch (RedirectionException $e) {
                $this->error->add($e->getMessage());
            }
        }

        $this->set('formContent', $this->post());
    }

    public function edit($id = null)
    {
        if (!$this->setEditing($id)) {
            $this->flash('error', t('That redirection no longer exists.'));
            $this->redirectToList();
        }
    }

    public function update($id = null)
    {
        $this->setEditing($id);

        if ($this->validateToken('lgt_redirections_update')) {
            try {
                $this->service->update(
                    (string) $id,
                    (string) $this->post('source'),
                    (int) $this->post('destination'),
                    $this->post('code'),
                );

                $this->flash('success', t('Redirection updated.'));
                $this->redirectToList();
            } catch (RedirectionException $e) {
                $this->error->add($e->getMessage());
            }
        }
    }

    public function delete($id = null)
    {
        if ($this->validateToken('lgt_redirections_delete')) {
            if ($this->service->delete((string) $id)) {
                $this->flash('success', t('Redirection removed.'));
            } else {
                $this->error->add(t('That redirection no longer exists.'));
            }

            if (!$this->error->has()) {
                $this->redirectToList();
            }
        }
    }

    public function bulk_delete()
    {
        if ($this->validateToken('lgt_redirections_delete')) {
            $ids = array_filter((array) $this->post('ids'), fn($id) => is_string($id) && preg_match('/^[0-9a-f-]{36}$/i', $id) === 1);

            if ($ids === []) {
                $this->error->add(t('Select at least one redirection.'));
            } else {
                $removed = 0;

                foreach ($ids as $id) {
                    $removed += $this->service->delete($id) ? 1 : 0;
                }

                $this->flash('success', t2('%d redirection removed.', '%d redirections removed.', $removed, $removed));
                $this->redirectToList();
            }
        }
    }

    public function import()
    {
        if ($this->validateToken('lgt_redirections_import')) {
            $file = $this->request->files->get('csv');

            if (!$file || !$file->isValid()) {
                $this->error->add(t('Choose a CSV file to upload.'));
            } elseif (!in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
                $this->error->add(t('The file must be a .csv file.'));
            } elseif ($file->getSize() > self::MAX_UPLOAD_BYTES) {
                $this->error->add(t('The file is too large (2MB maximum).'));
            } else {
                $report = $this->service->importCsv((string) file_get_contents($file->getPathname()));

                $this->app->make('session')->getFlashBag()->set('lgt_redirections_import', [$report]);

                if ($report['created'] > 0) {
                    $this->flash('success', t2('%d redirection imported.', '%d redirections imported.', $report['created'], $report['created']));
                }

                $this->redirectToList();
            }
        }
    }

    public function sample()
    {
        return new Response(CsvParser::sample(), 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="redirections-sample.csv"',
        ]);
    }

    public function getStatusOptions(): array
    {
        return [
            '302' => t('Temporary (302) - Recommended'),
            '301' => t('Permanent (301)'),
        ];
    }
}
