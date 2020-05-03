<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

class FileUploader
{
    private $encryptionKey;
    private $slugger;
    private $logger;

    public function __construct($encryptionKey, SluggerInterface $slugger, LoggerInterface $logger)
    {
        $this->encryptionKey = $encryptionKey;
        $this->slugger = $slugger;
        $this->logger = $logger;
    }

    public function getFileName(UploadedFile $file) {
        try {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $this->slugger->slug($originalFilename) . '.' .$file->guessExtension();
            return $filename;
        } catch (FileException $e) {
            throw new FileException($e->getMessage());
        }
    }

    public function getEncryptedFile(UploadedFile $file) {
        try {
            $fileContent = file_get_contents($file->getPathname());
            return $this->fileEncrypt($fileContent, $this->encryptionKey);
        } catch (FileException $e) {
            $this->logger->error('Error while encrypting the file');
            throw new FileException($e->getMessage());
        }
    }

    public function getDecryptedFile($encryptedContent) {
        try {
            return $this->fileDecrypt($encryptedContent, $this->encryptionKey);
        } catch (\Exception $e) {
            $this->logger->error('Error while decrypting the file');
            throw new \Exception($e->getMessage());
        }
    }


    private function fileEncrypt($data, $key) {
        $encryption_key = base64_decode($key);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    private function fileDecrypt($data, $key) {
        $encryption_key = base64_decode($key);
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    }

}