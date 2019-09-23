<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Subscriber;
use App\Enum\MaleClassificationEnum;
use Doctrine\ORM\QueryBuilder;
use ReflectionException;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SubscriberAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->clearExcept([
            'list',
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $callback = static function (ProxyQueryInterface $queryBuilder, string $alias, string $field, array $input) {
            $value = $input['value'];

            if (!$value instanceof Subscriber) {
                return null;
            }

            /** @var ProxyQuery|QueryBuilder $queryBuilder */
            $queryBuilder
                ->andWhere("$alias.id = :subscriber_id")
                ->setParameter('subscriber_id', $value->getId());

            return true;
        };

        $filter
            ->add('username', CallbackFilter::class, [
                'show_filter' => true,
                'callback' => $callback,
                'field_type' => 'text',
            ], EntityType::class, [
                'class' => Subscriber::class,
                'choice_label' => 'username',
            ])
            ->add('people', null, [
                'show_filter' => true,
            ])
            ->add('male', ChoiceFilter::class, [
                'show_filter' => true,
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => MaleClassificationEnum::getList(),
                ]
            ])
        ;
    }

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
            ->add('people', null, [
                'template' => 'Admin/subscriber.people.html.twig',
            ])
            ->add('male')
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
