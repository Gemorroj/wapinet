<?php

/**
 * This file is part of the FOSCommentBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Wapinet\CommentBundle\Controller;

use FOS\CommentBundle\Controller\ThreadController as BaseThreadController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Restful controller for the Threads.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class ThreadController extends BaseThreadController
{
    /**
     * Get the comments of a thread. Creates a new thread if none exists.
     *
     * @param Request $request Current request
     * @param string  $id      Id of the thread
     *
     * @return View
     */
    public function getThreadCommentsAction(Request $request, $id)
    {
        $page = $request->query->get('page', 1);
        $displayDepth = $request->query->get('displayDepth');
        $sorter = $request->query->get('sorter');
        $thread = $this->container->get('fos_comment.manager.thread')->findThreadById($id);

        // We're now sure it is no duplicate id, so create the thread
        if (null === $thread) {
            if (!$this->get('fos_comment.acl.thread')->canCreate()) {
                return new Response('<p>0 комментариев</p>');
            }

            // Decode the permalink for cleaner storage (it is encoded on the client side)
            $permalink = urldecode($request->query->get('permalink'));

            $thread = $this->container->get('fos_comment.manager.thread')
                ->createThread();
            $thread->setId($id);
            $thread->setPermalink($permalink);

            // Validate the entity
            $validator = $this->get('validator');
            $errors = $validator->validate($thread, 'NewThread');
            if (count($errors) > 0) {
                $view = View::create()
                    ->setStatusCode(Codes::HTTP_BAD_REQUEST)
                    ->setData(array('errors' => $errors))
                    ->setTemplate(new TemplateReference('FOSCommentBundle', 'Thread', 'errors'));

                return $this->getViewHandler()->handle($view);
            }

            // Add the thread
            $this->container->get('fos_comment.manager.thread')->saveThread($thread);
        }

        $viewMode = $request->query->get('view', 'tree');
        switch ($viewMode) {
            case self::VIEW_FLAT:
                /** @var $commentsByThread array('pagerfanta' => \Pagerfanta\Pagerfanta, 'comments' => array) */
                $commentsByThread = $this->container->get('wapinet_comment.manager.comment')->findCommentsByThread($thread, $displayDepth, $sorter, $page);
                $comments = $commentsByThread['comments'];
                $pagerfanta = $commentsByThread['pagerfanta'];

                // We need nodes for the api to return a consistent response, not an array of comments
                $comments = array_map(function($comment) {
                        return array('comment' => $comment, 'children' => array());
                    },
                    $comments
                );
                break;
            case self::VIEW_TREE:
            default:
                /** @var $commentTreeByThread array('pagerfanta' => \Pagerfanta\Pagerfanta, 'comments' => array) */
                $commentTreeByThread = $this->container->get('wapinet_comment.manager.comment')->findCommentTreeByThread($thread, $sorter, $displayDepth, $page);
                $comments = $commentTreeByThread['comments'];
                $pagerfanta = $commentTreeByThread['pagerfanta'];
                break;
        }

        $view = View::create()
            ->setData(array(
                'pagerfanta' => $pagerfanta,
                'comments' => $comments,
                'displayDepth' => $displayDepth,
                'sorter' => 'date',
                'thread' => $thread,
                'view' => $viewMode,
            ))
            ->setTemplate(new TemplateReference('FOSCommentBundle', 'Thread', 'comments'));

        // Register a special handler for RSS. Only available on this route.
        if ('rss' === $request->getRequestFormat()) {
            $templatingHandler = function($handler, $view, $request) {
                $view->setTemplate(new TemplateReference('FOSCommentBundle', 'Thread', 'thread_xml_feed'));

                return new Response($handler->renderTemplate($view, 'rss'), Codes::HTTP_OK, $view->getHeaders());
            };

            $this->get('fos_rest.view_handler')->registerHandler('rss', $templatingHandler);
        }

        return $this->getViewHandler()->handle($view);
    }


    /**
     * @return \FOS\RestBundle\View\ViewHandler
     */
    private function getViewHandler()
    {
        return $this->container->get('fos_rest.view_handler');
    }
}
