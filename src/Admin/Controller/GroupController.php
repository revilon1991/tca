<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Component\Tarantool\Adapter\TarantoolQueueAdapter;
use App\Consumer\GroupFetchConsumer;
use Exception;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class GroupController extends CRUDController
{
    /**
     * @var TarantoolQueueAdapter
     */
    private $tarantoolQueueAdapter;

    /**
     * @param TarantoolQueueAdapter $tarantoolQueueAdapter
     */
    public function __construct(TarantoolQueueAdapter $tarantoolQueueAdapter)
    {
        $this->tarantoolQueueAdapter = $tarantoolQueueAdapter;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function preCreate(Request $request, $object): ?Response
    {
        $form = $this->admin->getForm();
        $form->handleRequest($request);
        $username = $form->get('username')->getData();

        if (!$username) {
            return null;
        }

        /** @var Session $session */
        $session = $request->getSession();

        $this->tarantoolQueueAdapter->put(
            GroupFetchConsumer::QUEUE_FETCH_GROUP,
            [
                'username' => $username,
            ],
            [
                'key' => $username,
            ]
        );

        $session->getFlashBag()->add('success', "Группа $username добавлена в очередь");

        $redirectUrl = $this->admin->generateUrl('list');

        return new RedirectResponse($redirectUrl);
    }
}
