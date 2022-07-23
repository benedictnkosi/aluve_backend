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
        if (session_id() === '') {
            $logger->info("Session id is empty");
            session_start();
        }
    }

    public function createGuest($name, $phoneNumber, $email, $propertyUid): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {

            $property = $this->em->getRepository(Property::class)->findOneBy(array('uid' => $propertyUid));
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

    public function createAirbnbGuest($confirmationCode, $name): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //get property id
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('originUrl' => $confirmationCode));
            $property = $reservation->getRoom()->getProperty();
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('name' => $name,
                'property' => $property->getId(),
                'comments' => 'airbnb'));
            if($guest === null){
                $guest = new Guest();
                $guest->setName($name);
                $guest->setComments('airbnb');
                $guest->setProperty($property);
                $this->em->persist($guest);
                $this->em->flush($guest);
            }

            $reservation->setGuest($guest);

            $this->em->persist($reservation);
            $this->em->flush($reservation);
            $responseArray[] = array(
                'result_code' => 0,
                'result_message' => 'Successfully updated guest name'
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

    public function getGuests($filterValue, $propertyUid): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        try {
            $propertyApi = new PropertyApi($this->em, $this->logger);
            $propertyId =   $propertyApi->getPropertyIdByUid($propertyUid);
            if ($filterValue == 0) {
                $guest = $this->em->getRepository(Guest::class)->findBy(array('property' => $propertyId));
            } else {
                if (strlen($filterValue) > 4) {
                    $guest = $this->em->getRepository(Guest::class)->findOneBy(array('phoneNumber' => $filterValue, 'property' => $propertyId));
                } else {
                    $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $filterValue, 'property' => $propertyId));
                }
            }
            $responseArray = array();

            if($guest === null){
                $responseArray[] = array(
                    'result_code' => 1
                );
            }else{
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

    public function getGuestByPhoneNumber($phoneNumber, $propertyUid)
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $guest = null;
        $responseArray = array();
        try {
            $propertyApi = new PropertyApi($this->em, $this->logger);
            $propertyId =   $propertyApi->getPropertyIdByUid($propertyUid);
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('phoneNumber' => $phoneNumber, 'property' => $propertyId));
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

    public function getGuestByName($name, $propertyUid)
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $guest = null;
        $responseArray = array();
        try {
            $propertyApi = new PropertyApi($this->em, $this->logger);
            $propertyId =   $propertyApi->getPropertyIdByUid($propertyUid);
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('name' => $name, 'property' => $propertyId));
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


    function startsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        return substr($haystack, 0, $length) === $needle;
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
            $guestPreviousRooms = $this->getGuestPreviousRooms($guestId);
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