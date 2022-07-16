<?php

namespace App\Controller;

use App\Service\ICalApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ICalController extends AbstractController
{
    /**
     * @Route("api/ical/import/{roomId}")
     */
    public function importIcalReservations($roomId, LoggerInterface $logger, ICalApi $iCalApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $response = $iCalApi->importIcalForRoom($roomId);
        return  $this->json($response);
    }

    /**
     * @Route("api/ical/export/{roomId}")
     */
    public function exportIcalReservations($roomId, LoggerInterface $logger, ICalApi $iCalApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $ical = $iCalApi->exportIcalForRoom($roomId);
        $response = new Response($ical);
        //$response->headers->remove('Content-Disposition');
        $response->headers->add(array('Content-type'=>'text/calendar; charset=utf-8',  'Content-Disposition' => 'inline; filename=aluve_'.$roomId.'.ics'));
        return $response;
    }

}