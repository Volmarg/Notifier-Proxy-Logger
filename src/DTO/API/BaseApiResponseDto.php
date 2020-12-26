<?php


namespace App\DTO\API;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base ResponseDTO used for each response in the API calls
 * Each api dto should extend from this class as the fronted will try to build same dto on its side
 *
 * Class BaseInternalApiResponseDto
 * @package App\DTO\API\Internal
 */
class BaseApiResponseDto
{
    const KEY_CODE    = "code";
    const KEY_MESSAGE = "message";
    const KEY_SUCCESS = "success";

    /**
     * @var int $code
     */
    private int $code = Response::HTTP_BAD_REQUEST;

    /**
     * @var string $message
     */
    private string $message = "";

    /**
     * @var bool $success
     */
    private bool $success = false;

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::KEY_CODE    => $this->getCode(),
            self::KEY_MESSAGE => $this->getMessage(),
            self::KEY_SUCCESS => $this->isSuccess(),
        ];
    }

    /**
     * Will set the field of this dto to success response so that classes which extend this method will have
     * the base dto response `set to success`
     */
    public function prefillBaseFieldsForSuccessResponse(): void
    {
        $this->setCode(Response::HTTP_OK);;
        $this->setSuccess(true);
    }

    /**
     * Will set the field of this dto to bad request response so that classes which extend this method will have
     * the base dto response `set to bad request`
     */
    public function prefillBaseFieldsForBadRequestResponse(): void
    {
        $this->setCode(Response::HTTP_BAD_REQUEST);;
        $this->setSuccess(false);
    }

    /**
     * Will build internal server error response
     *
     * @return BaseApiResponseDto
     */
    public static function buildInternalServerErrorResponse(): BaseApiResponseDto
    {
        $dto = new BaseApiResponseDto();
        $dto->setCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $dto->setSuccess(false);

        return $dto;
    }

    /**
     * Will build internal server error response
     *
     * @return BaseApiResponseDto
     */
    public static function buildBadRequestErrorResponse(): BaseApiResponseDto
    {
        $dto = new BaseApiResponseDto();
        $dto->setCode(Response::HTTP_BAD_REQUEST);
        $dto->setSuccess(false);

        return $dto;
    }

    /**
     * @return JsonResponse
     */
    public function toJsonResponse(): JsonResponse
    {
        return new JsonResponse($this->toArray());
    }
}