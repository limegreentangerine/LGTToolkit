<?php

namespace LgtToolkit\Express;

use Core;
use Exception;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Express\ObjectBuilder;
use Concrete\Core\Express\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Application\Application;

class DuplicateExpressObjects
{
    protected Application $app;
    protected ObjectBuilder $builder;
    protected EntityManagerInterface $entityManager;
    protected ObjectManager $objectManager;

    public function __construct()
    {
        $this->app = Core::make('app');
        $this->builder = Core::make(ObjectBuilder::class);
        $this->entityManager = Core::make(EntityManagerInterface::class);
        $this->objectManager = new ObjectManager($this->app, $this->entityManager);
    }

    protected function getLocales()
    {
        $site = Core::make('site')->getActiveSiteForEditing();
        return $site->getLocales();
    }

    protected function getEntities()
    {
        $r = $this->entityManager->getRepository('\Concrete\Core\Entity\Express\Entity');
        $entities = [];
        foreach ($r->findPublicEntities() as $entity) {
            $permissions = new Checker($entity);
            if ($permissions->canViewExpressEntries()) {
                $entities[] = $entity;
            }
        }

        return (count($entities) > 0) ? $entities : false;
    }

    protected function getAssociationFunctionName(object $object)
    {
        $assocRemove = [
            'Concrete\Core\Entity\Express',
            '\\',
            'Association',
        ];
        $assocReplace = [
            '',
            'add',
            '',
        ];
        $className = str_replace($assocRemove, $assocReplace, get_class($object));

        return $className;
    }

    public function convert()
    {
        $locales = $this->getLocales();
        if (count($locales) < 2) {
            throw new Exception(t('Only one language in system'), 400);
        }

        $entities = $this->getEntities();
        if (!$entities) {
            throw new Exception(t('No public entities found'), 404);
        }

        foreach ($locales as $locale) {
            if ($locale->getIsDefault()) {
                continue;
            }

            $localeEntities = [];

            // create base object and add attributes
            foreach ($entities as $entity) {
                $cloneHandle = $entity->getHandle() . '_' . $locale->getLanguage();
                $clonePluralHandle = $entity->getPluralHandle() . '_' . $locale->getLanguage();
                $cloneName = $entity->getName() . ' (' . $locale->getLanguageText() . ')';

                if ($this->objectManager->getObjectByHandle($cloneHandle)) {
                    continue;
                }

                $clone = $this->objectManager->buildObject($cloneHandle, $clonePluralHandle, $cloneName);
                if (!$clone) {
                    throw new Exception(t('Express Object (%s) not found', $cloneHandle), 404);
                }

                $attributes = $entity->getAttributes();
                foreach ($attributes as $attribute) {
                    $attrType = $attribute->getAttributeTypeHandle();
                    $attrName = $attribute->getAttributeKeyName() . ' (' . $locale->getLanguageText() . ')';
                    $attrHandle = $attribute->getAttributeKeyHandle() . '_' . $locale->getLanguage();
                    $clone->addAttribute($attrType, $attrName, $attrHandle);
                }

                $response = new stdClass();
                $response->builder = $clone;
                $response->entity = $entity;
                $response->cloneEntity = $clone->save();

                $localeEntities[] = $response;
            }

            // loop newly created clones
            // and the builder/original relations
            foreach ($localeEntities as $le) {
                $builder = $le->builder;
                $entity = $le->entity;
                $cloneEntity = $le->cloneEntity;

                // associations
                $associations = $entity->getAssociations();
                foreach ($associations as $association) {
                    $targetEntity = $association->getTargetEntity();
                    $targetEntityHandle = $targetEntity->getHandle() . '_' . $locale->getLanguage();
                    $cloneTargetEntity = $this->objectManager->getObjectByHandle($targetEntityHandle);

                    if (!$cloneTargetEntity) {
                        throw new Exception(t('Cloned Target Entity (%s) not found for association', $targetEntityHandle), 404);
                    }

                    if (count($associations) === count($cloneTargetEntity->getAssociations())) {
                        continue;
                    }

                    $functionName = $this->getAssociationFunctionName($association);
                    $assocBuilder = $builder->buildAssociation();
                    $assocBuilder->__call($functionName, [ $cloneTargetEntity ]);
                    $builder->save();
                }

                // forms
                $forms = $entity->getForms();
                foreach ($forms as $form) {
                    $formName = 'Form';
                    $fieldsetName = 'Details';

                    $cloneForm = $cloneEntity->getForm($formName);
                    if (!$cloneForm) {
                        $cloneForm = $builder->buildForm($formName);
                    }

                    $cloneFieldset = $cloneForm->addFieldset($fieldsetName);
                    foreach ($cloneEntity->getAttributes() as $cloneAttr) {
                        $cloneFieldset->addAttributeKeyControl($cloneAttr->getAttributeKeyHandle());
                    }

                    $cloneForm = $cloneForm->save();

                    $entityManager = $builder->getEntityManager();
                    $cloneEntity->setDefaultViewForm($cloneForm);
                    $cloneEntity->setDefaultEditForm($cloneForm);
                    $entityManager->persist($cloneEntity);
                    $entityManager->flush();
                }
            }
        }

        echo t('Converted all entities');
        exit;
    }
}
