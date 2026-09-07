<?php
namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use Core;
use Concrete\Core\Permission\Checker;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Page\Controller\DashboardPageController;

class DuplicateExpress extends DashboardPageController
{
    public function on_start()
    {
        parent::on_start();

        $this->entityManager = Core::make(EntityManagerInterface::class);

        $this->set('locales', $this->getLocales());
        $this->set('expressObjects', $this->getExpressObjects());
    }

    public function getLocales()
    {
        $site = $this->app->make('site')->getActiveSiteForEditing();
        return $site->getLocales();
    }

    public function getExpressObjects()
    {
        $r = $this->entityManager->getRepository('\Concrete\Core\Entity\Express\Entity');
        $entities = [];
        foreach($r->findPublicEntities() as $entity) {
            $permissions = new Checker($entity);
            if ($permissions->canViewExpressEntries()) {
                $entities[] = $entity;
            }
        }

        return (count($entities) > 0) ? $entities : false;
    }
}
