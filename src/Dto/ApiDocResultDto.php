<?php

declare(strict_types=1);

namespace App\Dto;

use stdClass;
use Swagger\Annotations as SWG;
use Wakeapp\Bundle\ApiPlatformBundle\Dto\ApiResultDto;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;

/**
 * @SWG\Definition(
 *      type="object",
 *      description="Common API response object template",
 *      required={"code", "message"},
 * )
 */
class ApiDocResultDto extends ApiResultDto
{
    /**
     * @var int
     *
     * @SWG\Property(description="Response api code", example=0, default=0)
     */
    protected $code;

    /**
     * @var string
     *
     * @SWG\Property(description="Localized human readable text", example="Successfully")
     */
    protected $message;

    /**
     * @var DtoResolverInterface|null
     *
     * @SWG\Property(type="object", description="Some specific response data or null")
     */
    protected $data;

    /**
     * {@inheritdoc}
     */
    public function toArray(bool $onlyDefinedData = true): array
    {
        $result = parent::toArray($onlyDefinedData);

        if (empty($result['data'])) {
            $result['data'] = new stdClass();
        }

        return $result;
    }
}
