<?php

namespace App\Controller;

use App\Service\PaymentApi;
use App\Service\ReservationApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class PaymentController extends AbstractController
{
    /**
     * @Route("api/payment/{reservationId}/amount/{amount}/{paymentChannel}")
     */
    public function addPayment($reservationId, $amount,  $paymentChannel, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $response = $paymentApi->addPayment($reservationId, $amount, $paymentChannel);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/discount/{reservationId}/amount/{amount}")
     */
    public function addDiscount($reservationId, $amount, LoggerInterface $logger, Request $request, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $response = $paymentApi->addDiscount($reservationId, $amount);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }
    /**
     * @Route("public/payfast_notify")
     * @throws \Exception
     */
    public function payfast_notify(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $logger->info("reservation ID: " . $request->get('item_description'));
        $logger->info("amount paid: " . $request->get('amount_gross'));
        $reservationId = $request->get('item_description');
        $amount = $request->get('amount_gross');

        $response = $paymentApi->addPayment($reservationId, $amount, "payfast");
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("public/payfast_notify_test")
     * @throws \Exception
     */
    public function payfast_notify_test(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $reservationId = "[74,75]";
        $amount = "10.00";

        $response = $paymentApi->addPayment($reservationId, $amount, "payfast");
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

}