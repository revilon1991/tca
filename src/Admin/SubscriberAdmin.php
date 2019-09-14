<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;

class SubscriberAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('firstName')
            ->add('lastName')
            ->add('username')
            ->add('phone')
            ->add('type')
            ->add('people')
            ->add('createdAt')
            ->add('groupList', null, [
                'template' => 'Admin/subscriber.groupList.html.twig',
            ])
            ->add('photoList', null, [
                'template' => 'Admin/subscriber.photoList.html.twig',
                'header_style' => 'width: 20%',
            ])
        ;
    }
}
