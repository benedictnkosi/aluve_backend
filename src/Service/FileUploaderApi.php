<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class FileUploaderApi
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function upload($uploadDir, $file, $filename)
    {
        try {

            $file->move($uploadDir, $filename);
        } catch (FileException $e){

            $this->logger->error('failed to upload image: ' . $e->getMessage());
            throw new FileException('Failed to upload file');
        }
    }
}