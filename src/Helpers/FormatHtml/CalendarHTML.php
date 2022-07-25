<?php

namespace App\Helpers\FormatHtml;

use App\Service\AddOnsApi;
use App\Service\BlockedRoomApi;
use App\Service\CleaningApi;
use App\Service\GuestApi;
use App\Service\NotesApi;
use App\Service\PaymentApi;
use App\Service\ReservationApi;
use App\Service\RoomApi;
use DateInterval;
use DateTime;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class CalendarHTML
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    public function formatHtml($propertyUid): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $htmlString = "";

        $roomsApi = new RoomApi($this->em, $this->logger);
        $reservationApi = new ReservationApi($this->em, $this->logger);
        $blockRoomApi = new BlockedRoomApi($this->em, $this->logger);
        $numberOfDays = 180;
        $numberOfFirstOfMonth = 0;

        //headings
        $htmlString .= "<tr><th class='calendar-table-header'>Room Name</th>";

        for ($x = 0; $x <= $numberOfDays; $x++) {
            $todayDate = new DateTime();
            $todayDate->add(DateInterval::createFromDateString('yesterday'));
            $tempDate = $todayDate->add(new DateInterval('P' . $x . 'D'));

            if (strcmp($tempDate->format('d'), "01") == 0 ) {
                $htmlString .= '<th class="new-month">' . $tempDate->format('M') . '</th>';
                $numberOfFirstOfMonth++;
            }

            if (strcmp($tempDate->format('D'), "Sat") == 0 || strcmp($tempDate->format('D'), "Sun") == 0) {
                $htmlString .= '<th class="weekend">' . $tempDate->format('D') . '<br>' . $tempDate->format('d') . '</th>';
            } else {
                $htmlString .= '<th>' . $tempDate->format('D') . '<br>' . $tempDate->format('d') . '</th>';
            }
        }
        $htmlString .= '</tr>';

        $rooms = $roomsApi->getRoomsEntities($propertyUid);
        foreach ($rooms as $room) {
            $htmlString .= '<tr><th class="headcol">' . $room->getName() . '</th>';
            $reservations = $reservationApi->getUpComingReservations($propertyUid, $room->getId(), true);
            $blockedRooms = $blockRoomApi->getBlockedRooms($propertyUid, $room->getId());

            if ($reservations === null && $blockedRooms === null) {
                $htmlString .= str_repeat('<td class="available"></td>', $numberOfDays + 1 + $numberOfFirstOfMonth);
            } else {
                for ($x = 0; $x <= $numberOfDays; $x++) {
                    $todayDate = new DateTime();
                    $todayDate->add(DateInterval::createFromDateString('yesterday'));
                    $tempDate = $todayDate->add(new DateInterval('P' . $x . 'D'));
                    $isDateBlocked = false;
                    $isDateBooked = false;
                    $isDateBookedButOpen = false;
                    $resID = "";
                    $guestName = "";
                    $blockNote = "";
                    $isCheckInDay = false;

                    if (strcmp($tempDate->format('d'), "01") == 0 ) {
                        $htmlString .= '<td class="new-month"></td>';
                    }

                    $this->logger->debug("outside foreach for reservations temp date is " . $todayDate->format("Y-m-d") . " x is $x");

                    foreach ($reservations as &$reservation) {
                        $isCheckInDay = false;
                        $this->logger->debug("Check if temp date " . $tempDate->format("Y-m-d") . " and res " . $reservation->getId() . " check in date is " . $reservation->getCheckIn()->format("Y-m-d") . "check out date " . $reservation->getCheckOut()->format("Y-m-d"));
                        if ($tempDate >= $reservation->getCheckIn() && $tempDate < $reservation->getCheckOut()) {
                            $this->logger->debug("check passed");
                            if (strcasecmp($reservation->getStatus()->getName(), "confirmed") === 0) {
                                $resID = $reservation->getId();
                                $isDateBooked = true;
                                $guestName = $reservation->getGuest()->getName();
                                if (strcasecmp($tempDate->format("Y-m-d"), $reservation->getCheckIn()->format("Y-m-d")) === 0) {
                                    $this->logger->debug("Check in day is true because tempdate is " . $tempDate->format("Y-m-d") . " and res " . $reservation->getId() . " check in date is " . $reservation->getCheckIn()->format("Y-m-d"));
                                    $isCheckInDay = true;
                                }
                                break;
                            } else if (strcasecmp($reservation->getStatus()->getName(), "pending") == 0) {
                                $isDateBookedButOpen = true;
                                break;
                            }

                        } else {
                            $this->logger->debug("check failed");

                        }
                    }

                    $this->logger->debug("blocked rooms");
                    if ($blockedRooms != null) {

                        foreach ($blockedRooms as $blockedRoom) {
                            if ($tempDate >= $blockedRoom->getFromDate() && $tempDate < $blockedRoom->getToDate()) {
                                $this->logger->debug("date is blocked - temp " . $tempDate->format("Y-m-d") . " getFromDate " . $blockedRoom->getFromDate()->format("Y-m-d") . " getToDate " . $blockedRoom->getToDate()->format("Y-m-d"));
                                $isDateBlocked = true;
                                $blockNote = $blockedRoom->getComment();
                                break;
                            }
                        }
                    }else{
                        $this->logger->debug("blockedRooms array is not null");
                    }

                    $this->logger->debug("checking if date booked");
                    if ($isDateBooked) {
                        if ($isCheckInDay === true) {
                            $htmlString .= '<td  class="booked checkin" data-resid="' . $resID . '" title="' . $guestName . '"><img  src="images/' . $reservation->getOrigin() . '.png"  data-resid="' . $resID . '" alt="checkin" class="image_checkin"></td>';
                        } else {
                            $htmlString .= '<td  class="booked" data-resid="' . $resID . '" title="' . $guestName . '"></td>';
                        }
                    } else if ($isDateBlocked) {
                        $htmlString .= '<td class="blocked" title="' . $blockNote . '"></td>';
                    } else if ($isDateBookedButOpen) {
                        $htmlString .= '<td class="pending"></td>';
                    } else {
                        $htmlString .= '<td class="available"></td>';
                    }

                }
            }
            $htmlString .= '</tr>';
        }
        return $htmlString;
    }
}