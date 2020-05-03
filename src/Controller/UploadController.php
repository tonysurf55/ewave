<?php

namespace App\Controller;

use App\Entity\Upload;
use App\Form\UploadFormType;
use App\Service\FileUploader;
use App\Repository\UploadRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\Exception\NoFileException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Psr\Log\LoggerInterface;

/**
 * Controller used to manage uploads.
 */
class UploadController extends AbstractController
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
       $this->security = $security;
    }

    /**
     * @Route("/uploads/{page}", name="upload_index")
     * @param integer $page The current page passed via URL
     * @param UploadRepository $UploadRepository the repository of the upload entity
     */
    public function index($page = 1, UploadRepository $UploadRepository)
    {
        /** @var \App\Entity\User */
        $user = $this->security->getUser();
        $uploads = $UploadRepository->findByUser($user->getId());

        $limit = 10;
        $uploads = $UploadRepository->findByUser($user->getId(), $page, $limit);
        $maxPages = ceil($uploads->count() / $limit);
        return $this->render('upload/index.html.twig', [
            'uploads' => $uploads, 
            'maxPages' => $maxPages,
            'currentPage' => $page
        ]);
    }

    /**
     * @Route("/upload/get/{uploadId}", name="upload_get")
     * @param integer $uploadId The Id of the upload
     * @param UploadRepository $UploadRepository the repository of the upload entity
     * @param FileUploader $FileUploader Service to upload / downwload files
     */
    public function getFile($uploadId, UploadRepository $UploadRepository, FileUploader $fileUploader)
    {
        /** @var \App\Entity\User */
        $user = $this->security->getUser();
        $upload = $UploadRepository->findOneById($uploadId, $user->getId());

        if (empty($upload)) {
            throw new \Exception('No file found with the Id ' . $uploadId);
        }        

        $fileContent = $fileUploader->getDecryptedFile($upload->getFile());
        $response = new Response($fileContent);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $upload->getName()
        );
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }

    /**
     * @Route("/upload/new", name="upload_new")
     * @param Request $request
     * @param FileUploader $FileUploader Service to upload / downwload files
     * @param LoggerInterface $logger
     */
    public function new(Request $request, FileUploader $fileUploader, LoggerInterface $logger): Response
    {
        $upload = new Upload();
        $form = $this->createForm(UploadFormType::class, $upload);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User */
            $user = $this->security->getUser();

            //Extra datas for the logs
            $logDatas = ['userId' => $user->getId(), 'username' =>  $user->getUsername()];

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile */
            $file = $form->get('file')->getData();

            if ($file) {

                try {
                    $filename = $fileUploader->getFileName($file);
                    $encryptedFile = $fileUploader->getEncryptedFile($file);
                    $upload->setFile($encryptedFile);
                    $upload->setName($filename);
                    $upload->setUser($user);
                    $logDatas = array_merge($logDatas, ['user' => $user->getId(), 'filename' => $filename ]);
                    $upload->setCreated(new \DateTime());
                } catch (FileException $e) {
                    $logger->error($e->getMessage(), $logDatas);
                    throw new FileException($e->getMessage());
                }

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($upload);
                $entityManager->flush();

                $logger->info('File ' . $filename . ' uploaded successfuly.', $logDatas);
                $this->addFlash('success', 'File uploaded successfuly');
    
            } else {
                $logger->error('No file given.', $logDatas);
                throw new NoFileException('Please provide a file to upload');
            }

        }

        return $this->render('upload/new.html.twig', [
            'uploadForm' => $form->createView(),
        ]);
    }


}
