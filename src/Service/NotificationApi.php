<?php

namespace App\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationApi
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        if(session_id() === ''){
            $logger->info("Session id is empty");
            session_start();
        }
    }

    public function getNotifications(): string
    {
        return $this->getLongStayCleaningNotifications();
    }

    public function getLongStayCleaningNotifications(){
        $this->logger->debug("Starting Method: " . __METHOD__);
        //get active reservations where cleaning is more than 2 days
        $reservationApi = new ReservationApi($this->em, $this->logger);
        $reservations = $reservationApi->getStayOversReservations();
        $CleaningApi = new CleaningApi($this->em, $this->logger);
        $notificationsHtml = "";

        foreach ($reservations as $reservation){
            $this->logger->debug("found reservations " . $reservation->getId());
            $today = new DateTime();
            $checkInDate = $reservation->getCheckin();
            $lastCleaningDate = null;

            //get last cleaning date
            $cleanings = $CleaningApi->getReservationLastCleaning($reservation->getId());
            if(count($cleanings) > 0){
                $this->logger->debug("found cleanings " . count($cleanings));
                $lastCleaningDate = $cleanings[0]->getDate();
            }else{
                $this->logger->debug("did not find cleanings");
            }

            //is check in more than 2 days ago
            $interval = $today->diff($checkInDate)->days;
            $this->logger->debug("check in date diff is " . $interval);
            if($interval>2){

                //check if the room has been cleaned since check in
                if($lastCleaningDate !== null){
                    //check if the cleaning is not older than 2 days
                    $interval = $today->diff($lastCleaningDate)->days;
                    $this->logger->debug("cleaning in date diff is " . $interval);
                    if($interval > 2){
                        $notificationsHtml .= '<h5 class="notification_message borderAndPading">' . $reservation->getRoom()->getName(). " not cleaned in $interval days. last cleaning was on " . $lastCleaningDate->format("d M") . '</h5>';
                    }
                }else{
                    // add notification if the room has never been cleaned since check in
                    $notificationsHtml .= '<h5 class="notification_message borderAndPading">' . $reservation->getRoom()->getName(). " not cleaned since guest checked in $interval days  on the " . $checkInDate->format("d M") . '</h5>';
                }
            }
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $notificationsHtml;
    }
}