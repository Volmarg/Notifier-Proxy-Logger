<?php

namespace App\Action\Modules\Mailing;

use App\Controller\Application;
use App\Controller\Core\Controllers;
use App\DTO\API\BaseApiResponseDto;
use App\DTO\API\Internal\GetAllEmailsAccountsResponseDto;
use App\DTO\API\Internal\GetAllEmailsResponseDto;
use App\DTO\Modules\Mailing\MailAccountDTO;
use App\DTO\Modules\Mailing\MailDTO;
use App\DTO\Modules\Mailing\SendTestMailDTO;
use App\Entity\Modules\Mailing\Mail;
use App\Services\Internal\FormService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/modules/mailing", name: "modules_mailing_")]
class MailingAction extends AbstractController
{
    /**
     * @var Application $application
     */
    private Application $application;

    /**
     * @var NotifierInterface $notifier
     */
    private NotifierInterface $notifier;

    /**
     * @var FormService $formService
     */
    private FormService $formService;

    /**
     * @var Controllers $controllers
     */
    private Controllers $controllers;

    public function __construct(Application $application, NotifierInterface $notifier, FormService $formService, Controllers $controllers)
    {
        $this->controllers = $controllers;
        $this->application = $application;
        $this->formService = $formService;
        $this->notifier    = $notifier;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/send-test-mail", name: "send_test_mail", methods: ["POST"])]
    public function sendTestMail(Request $request): JsonResponse
    {
        try{
            $testMailForm = $this->application->getForms()->getSendTestMailForm();
            $message      = $this->application->trans('pages.mailing.sendTestMail.messages.success');

            $baseResponseDto = new BaseApiResponseDto();
            $baseResponseDto->prefillBaseFieldsForSuccessResponse();

            $testMailForm = $this->formService->handlePostFormForAxiosCall($testMailForm, $request);
            if( $testMailForm->isSubmitted() && $testMailForm->isValid() ){
                /**
                 * @var SendTestMailDTO $sendTestMailDto
                 */
                $sendTestMailDto = $testMailForm->getData();

                $mail = new Mail();
                $mail->setBody($sendTestMailDto->getMessageBody());
                $mail->setSubject($sendTestMailDto->getMessageTitle());
                $mail->setToEmails([$sendTestMailDto->getReceiver()]);

                $this->controllers->getMailingController()->sendSingleEmail($mail);
            }elseif( $testMailForm->isSubmitted() && !$testMailForm->isValid() ){
                $message = $this->application->trans('pages.mailing.sendTestMail.messages.fail');
                $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
            }

            $baseResponseDto->setMessage($message);
            return $baseResponseDto->toJsonResponse();
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.sendTestMail.messages.fail');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

    /**
     * Will return all emails
     * @return JsonResponse
     */
    #[Route("/get-all-emails", name: "get_all_emails", methods: ["GET"])]
    public function getAllEmails(): JsonResponse
    {
        try{
            $mails = $this->controllers->getMailingController()->getAllEmails();

            $arrayOfDtosJsons = [];
            foreach($mails as $mail){
                $mailDto = new MailDTO();
                $mailDto->setBody($mail->getBody());
                $mailDto->setSubject($mail->getSubject());
                $mailDto->setCreated($mail->getCreated()->format("Y-m-d H:i:s"));
                $mailDto->setToEmails($mail->getToEmails());
                $mailDto->setStatus($mail->getStatus());

                $arrayOfDtosJsons[] = $mailDto->toJson();
            }

            $responseDto = new GetAllEmailsResponseDto();
            $responseDto->prefillBaseFieldsForSuccessResponse();
            $responseDto->setEmailsJsons($arrayOfDtosJsons);

            return $responseDto->toJsonResponse();
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.history.messages.errors.couldNotGetAllSentEmails');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

    /**
     * Will return all mails accounts
`     * @return JsonResponse
     */
    #[Route("/get-all-mails-accounts", name: "get_all_mails_accounts", methods: ["GET"])]
    public function getAllMailsAccount(): JsonResponse
    {
        try{
            $mailsAccounts = $this->controllers->getMailAccountController()->getAllMailAccounts();

            $arrayOfDtosJsons = [];
            foreach($mailsAccounts as $mailAccount){
                $mailAccountDto = new MailAccountDTO();
                $mailAccountDto->setId($mailAccount->getId());
                $mailAccountDto->setName($mailAccount->getName());
                $mailAccountDto->setClient($mailAccount->getClient());
                $mailAccountDto->setLogin($mailAccount->getLogin());
                $mailAccountDto->setPassword($mailAccount->getPassword()); // todo: encrypt that somewhere in future

                $arrayOfDtosJsons[] = $mailAccountDto->toJson();
            }

            $responseDto = new GetAllEmailsAccountsResponseDto();
            $responseDto->prefillBaseFieldsForSuccessResponse();
            $responseDto->setEmailsAccountsJsons($arrayOfDtosJsons);

            return $responseDto->toJsonResponse();
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.getAllMailsAccount.messages.errors.couldNotGetAllEmailsAccounts');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

    /**
     * Handles the frontend (axios) request to add the mail account
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/add-mail-account", name: "add_mail_account", methods: ["POST"])]
    public function addMailAccount(Request $request): JsonResponse
    {
        try{
            $successMessage = $this->application->trans('pages.mailing.addMailAccount.messages.success');

            $baseResponseDto = new BaseApiResponseDto();
            $baseResponseDto->prefillBaseFieldsForSuccessResponse();
            $baseResponseDto->setMessage($successMessage);

            $addMailAccountForm = $this->application->getForms()->getAddMailAccount();
            $this->formService->handlePostFormForAxiosCall($addMailAccountForm, $request);

            if( $addMailAccountForm->isSubmitted() && $addMailAccountForm->isValid() ){
                $mailAccount = $addMailAccountForm->getData();
                $this->controllers->getMailAccountController()->saveMailAccount($mailAccount);
            }else{
                $failMessage = $this->application->trans('pages.mailing.addMailAccount.messages.invalidDataHasBeenProvided');

                $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                $baseResponseDto->setMessage($failMessage);
            }
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.addMailAccount.messages.fail');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }

        return $baseResponseDto->toJsonResponse();
    }

    /**
     * Handles the removal of single mail account
     *
     * @param string mailAccountId
     * @return JsonResponse
     */
    #[Route("/remove-mail-account/{mailAccountId}", name: "remove_mail_account", methods: [ "GET" ])]
    public function removeMailAccount(string $mailAccountId): JsonResponse
    {
        try{
            $mailAccount = $this->controllers->getMailAccountController()->getOneById($mailAccountId);
            if( empty($mailAccount) ){
                $message = $this->application->trans('pages.mailing.removeMailAccount.messages.fail.noMailAccountWasFound');
                return BaseApiResponseDto::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $this->controllers->getMailAccountController()->hardDelete($mailAccount);

            $successMessage = $this->application->trans('pages.mailing.removeMailAccount.messages.success');
            $baseResponseDto = new BaseApiResponseDto();
            $baseResponseDto->prefillBaseFieldsForSuccessResponse();
            $baseResponseDto->setMessage($successMessage);
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.removeMailAccount.messages.fail.failedToRemove');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }

        return $baseResponseDto->toJsonResponse();
    }

}