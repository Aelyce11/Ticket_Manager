<?php

namespace App\Controller;

use DateTime;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
/**
 * @Route("/")
 */
class TicketController extends AbstractController
{
    /**
     * @Route("/", name="ticket_index", methods={"GET"})
     */
    public function index(TicketRepository $ticketRepository, Security $security): Response
    {
        if ($security->getUser())
        {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->render('ticket/index.html.twig', [
                    'tickets' => $ticketRepository->findAll(),
            ]);
            }
            return $this->render('ticket/index.html.twig', [
                'tickets' => $security->getUser()->getTickets(),
            ]);
        }
        return $this->render('ticket/index.html.twig', [
            'tickets' => null,
        ]);
    }

    /**
     * @Route("/new", name="ticket_new", methods={"GET","POST"})
     */
    public function new(Request $request, Security $security): Response
    {
        $ticket = new Ticket();
        $ticket->setCreatedAt(new DateTime());
        $ticket->setUpdatedAt(new DateTime());
        $ticket->addUser($security->getUser());
        $canEditUsers = false;
        if ($this->isGranted('ROLE_ADMIN')) {
            $canEditUsers = true;
        }
        $form = $this->createForm(TicketType::class, $ticket, [
            'canEditUsers' => $canEditUsers,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($ticket);
            $entityManager->flush();

            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_show", methods={"GET", "POST"})
     */
    public function show(Request $request, Security $security, Ticket $ticket): Response
    {
        $comment = new Comment();
        $comment->setCreatedAt(new DateTime());
        $comment->setUpdatedAt(new DateTime());
        $comment->setTicket($ticket);
        $comment->setUser($security->getUser());
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="ticket_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Ticket $ticket): Response
    {
        $ticket->setUpdatedAt(new DateTime());
        $canEditUsers = false;
        if ($this->isGranted('ROLE_ADMIN')) {
            $canEditUsers = true;
        }
        $form = $this->createForm(TicketType::class, $ticket, [
            'canEditUsers' => $canEditUsers,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Ticket $ticket): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($ticket);
            $entityManager->flush();
        }

        return $this->redirectToRoute('ticket_index');
    }
}
