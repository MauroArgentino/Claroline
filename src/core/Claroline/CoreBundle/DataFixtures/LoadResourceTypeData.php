<?php

namespace Claroline\CoreBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Entity\Resource\MetaType;
use Claroline\CoreBundle\Entity\Resource\ResourceTypeCustomAction;

/**
 * Resource types data fixture.
 */
class LoadResourceTypeData extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    /** @var ContainerInterface $container */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Loads one meta type (document) and four resource types handled by the platform core :
     * - File
     * - Directory
     * - Link
     * - Text
     * All these resource types have the 'document' meta type.
     *
     * @param ObjectManager $manager
     */
    public function load (ObjectManager $manager)
    {
        $documentMetatype = new MetaType();
        $documentMetatype->setName('document');
        $manager->persist($documentMetatype);
        // resource type attributes : name, listable, navigable, class, download
        $resourceTypes = array(
            array('file', true, false, 'Claroline\CoreBundle\Entity\Resource\File', true),
            array('directory', true, true, 'Claroline\CoreBundle\Entity\Resource\Directory', true),
            array('text', true, false, 'Claroline\CoreBundle\Entity\Resource\Text', true)
        );

        $i=0;

        foreach ($resourceTypes as $attributes) {
            $type = new ResourceType();
            $type->setName($attributes[0]);
            $type->setVisible($attributes[1]);
            $type->setBrowsable($attributes[2]);
            $type->setClass($attributes[3]);
            $type->addMetaType($documentMetatype);
            $manager->persist($type);

            if(isset($customActions[$i])){
                if ($customActions[$i] !== null) {
                    $actions = new ResourceTypeCustomAction();
                    $actions->setAction($customActions[$i][0]);
                    $actions->setAsync($customActions[$i][1]);
                    $actions->setResourceType($type);
                    $manager->persist($actions);
                }
            }

            $this->addReference("resource_type/{$attributes[0]}", $type);
            $i++;
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2;
    }
}