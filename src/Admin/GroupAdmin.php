<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;

class GroupAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('username')
            ->add('type')
            ->add('title')
            ->add('about')
            ->add('subscriberCount')
            ->add('lastUpdate')
            ->add('photoList', null, [
                'template' => 'Admin/group.photoList.html.twig',
            ])
        ;
    }
}
