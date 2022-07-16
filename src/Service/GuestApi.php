<?php

namespace App\Service;

use App\Entity\Property;
use App\Entity\Reservations;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Guest;

class GuestApi
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

    public function createGuest($name,$phoneNumber, $email): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            if(!isset($_SESSION['PROPERTY_ID'])) {
                $responseArray[] = array(
                    'result_message' => 'Property ID not set, please logout and login again',
                    'result_code'=> 1
                );
            }else{
                $property = $this->em->getRepository(Property::class)->findOneBy(array('id'=>$_SESSION['PROPERTY_ID']));
                $guest = new Guest();
                $guest->setName($name);
                $guest->setPhoneNumber($phoneNumber);
                $guest->setEmail($email);
                $guest->setProperty($property);

                $this->em->persist($guest);
                $this->em->flush($guest);
                $responseArray[] = array(
                    'result_code' => 0,
                    'result_message' => 'Successfully created guest',
                    'guest' => $guest
                );
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage()
            );
            $this->logger->info(print_r($responseArray, true));
        }

        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateGuestPhoneNumber($resId, $phoneNumber): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id' => $resId));
            $guest = $reservation->getGuest();
            $guest->setIdNumber($phoneNumber);
            $this->em->persist($guest);
            $this->em->flush($guest);
            $responseArray[] = array(
                'result_code' => 0,
                'result_message' => 'Successfully updated guest phone number'
            );
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage()
            );
            $this->logger->info(print_r($responseArray, true));
        }

        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateGuestIdNumber($guestId, $IdNumber): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            $guest->setIdNumber($IdNumber);
            $this->em->persist($guest);
            $this->em->flush($guest);
            $responseArray[] = array(
                'result_code' => 0,
                'result_message' => 'Successfully updated guest phone number'
            );
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage()
            );
            $this->logger->info(print_r($responseArray, true));
        }

        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function blockGuest($guestId, $reason): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            $guest->setState("blocked");
            $guest->setComments($reason);
            $this->em->persist($guest);
            $this->em->flush($guest);
            $responseArray[] = array(
                'result_code' => 0,
                'result_message' => 'Successfully blocked guest'
            );
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage()
            );
            $this->logger->info(print_r($responseArray, true));
        }

        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getGuests($filterValue): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        try {
            if(!isset($_SESSION['PROPERTY_ID'])) {
                $responseArray[] = array(
                    'result_message' => 'Property ID not set, please logout and login again',
                    'result_code'=> 1
                );
            }else{
                if ($filterValue == 0) {
                    $guests = $this->em->getRepository(Guest::class)->findBy(array('property'=>$_SESSION['PROPERTY_ID']));
                } else {
                    if (strlen($filterValue) > 4) {
                        $guests = $this->em->getRepository(Guest::class)->findBy(array('phoneNumber' => $filterValue, 'property'=>$_SESSION['PROPERTY_ID']));
                    } else {
                        $guests = $this->em->getRepository(Guest::class)->findBy(array('id' => $filterValue,  'property'=>$_SESSION['PROPERTY_ID']));
                    }
                }
                $responseArray = array();

                foreach ($guests as $guest) {
                    $responseArray[] = array(
                        'id' => $guest->getId(),
                        'name' => $guest->getName(),
                        'image_id' => $guest->getIdImage(),
                        'phone_number' => $guest->getPhoneNumber(),
                        'email' => $guest->getEmail(),
                        'state' => $guest->getState(),
                        'comments' => $guest->getComments(),
                        'result_code' => 0
                    );
                }
            }

        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->info(print_r($responseArray, true));
        }

        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getGuestByPhoneNumber($phoneNumber)
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $guest = null;
        try {
            if(!isset($_SESSION['PROPERTY_ID'])) {
                $responseArray[] = array(
                    'result_message' => 'Property ID not set, please logout and login again',
                    'result_code'=> 1
                );
                $this->logger->info(print_r($responseArray, true));
            }else{
                $guest =  $this->em->getRepository(Guest::class)->findOneBy(array('phoneNumber' => $phoneNumber, 'property'=>$_SESSION['PROPERTY_ID']));
            }
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->info(print_r($responseArray, true));
        }

        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $guest;
    }

    function startsWith( $haystack, $needle ): bool
    {
        $length = strlen( $needle );
        return substr( $haystack, 0, $length ) === $needle;
    }

    public function getGuestStaysCount($guestId): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $stays = $this->em->getRepository(Reservations::class)->findBy(array('guest' => $guestId,
                'status' => 'confirmed'));
            $responseArray[] = array(
                'result_message' => count($stays),
                'result_code' => 0
            );
        } catch (Exception $exception) {
            $responseArray = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->info(print_r($responseArray, true));
        }
        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getGuestPreviousRooms($guestId): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $reservations = $this->em->getRepository(Reservations::class)->findBy(array('guest' => $guestId,
                'status' => 'confirmed'));
            foreach ($reservations as $item) {
                $responseArray[] = array(
                    'rooms' => $item->getRoom(),
                    'result_code' => 0
                );
            }
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->info(print_r($responseArray, true));
        }

        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function hasGuestStayedInRoom($guestId, $roomId): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $guestPreviousRooms = getGuestPreviousRooms($guestId);
            foreach ($guestPreviousRooms as $room) {
                if ($room->getId() == $roomId) {
                    $responseArray[] = array(
                        'result_message' => true,
                        'result_code' => 0
                    );
                }
            }
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->info(print_r($responseArray, true));
        }

        $this->logger->info("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

}