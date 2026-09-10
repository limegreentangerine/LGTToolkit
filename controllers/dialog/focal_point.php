<?php

namespace Concrete\Package\LgtToolkit\Controller\Dialog;

use Concrete\Core\File\File;
use Concrete\Core\Http\Request;
use Concrete\Core\View\DialogView;
use Concrete\Core\Permission\Checker;
use Doctrine\ORM\EntityManagerInterface;
use LgtToolkit\Entity\File\ImageFocalPoint;
use Concrete\Core\Support\Facade\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Concrete\Controller\Backend\UserInterface\File as BackendInterfaceFileController;

class FocalPoint extends BackendInterfaceFileController
{
    protected $viewPath = '/dialogs/focal_point';

    public function __construct()
    {
        $this->app = Application::getFacadeApplication();
        $this->error = $this->app->make('error');
        $this->view = new DialogView($this->viewPath);
        /** @var \Concrete\Core\View\View $view */
        $view = $this->view;
        $view->setPackageHandle('lgt_toolkit');
        $view->setController($this);
        $this->request = Request::getInstance();
    }

    public function canAccess()
    {
        $permissions = new Checker($this->file);
        return $permissions->canEditFileProperties();
    }

    public function on_start()
    {
        parent::on_start();

        $html = $this->app->make('helper/html');
        $this->addHeaderItem($html->css('focal_point.css', 'lgt_toolkit'));
    }

    public function view()
    {
        $this->file = File::getByID($_GET['fID']);
        $version = $this->file->getRecentVersion();

        $this->set('file', $version);
        $this->set('focal_point', $this->app->make('focal_point')->getFocalPoint($this->file, true));
        $this->set('form', $this->app->make('helper/form'));
        $this->set('token', $this->app->make('token'));
    }

    public function submit()
    {
        $entityManager = $this->app->make(EntityManagerInterface::class);

        $post = $this->post();
        $post['fID'] = $this->file->getFileID();

        if (array_key_exists('fpID', $post)) {
            $focalPoint = ImageFocalPoint::getByID($post['fpID']);
        }

        if (!isset($focalPoint) || !$focalPoint) {
            $focalPoint = new ImageFocalPoint();
        }

        if ($post['fpDelete'] < 1) {
            $focalPoint->setFile($this->file);
            $focalPoint->setX($post['fpX']);
            $focalPoint->setY($post['fpY']);

            $entityManager->persist($focalPoint);
            $this->flash('success', t('Focal Point Set Successfully.'));
        } else {
            $entityManager->remove($focalPoint);
            $this->flash('error', t('Focal Point Removed.'));
        }

        $entityManager->flush();


        return new JsonResponse(json_encode((array) $focalPoint));
    }
}
