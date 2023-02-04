<?php

namespace Laravel\Passport\Bridge;

use DateTimeImmutable;
use Laravel\Passport\DeviceCodeRepository;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\DeviceCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class DeviceCode implements DeviceCodeEntityInterface
{
    use EntityTrait, TokenEntityTrait, DeviceCodeTrait {
        checkRetryFrequency as parentCheckRetryFrequency;
    }

    /**
     * @param  \Laravel\Passport\DeviceCodeRepository  $deviceCodeRepository
     * @return void
     */
    function __construct(DeviceCodeRepository $deviceCodeRepository)
    {
        $this->deviceCodeRepository = $deviceCodeRepository;
    }

    /**
     * {@inheritdoc}
     */
    function setUserCode($userCode)
    {
        if (!preg_match("/\w+-\w+/", $userCode)) {
            $userCode = substr_replace($userCode, '-', 4, 0);
        }

        $this->userCode = $userCode;
    }

    /**
     * {@inheritdoc}
     */
    function checkRetryFrequency(DateTimeImmutable $nowDateTime)
    {
        $slowDownSeconds = $this->parentCheckRetryFrequency($nowDateTime);

        if ($slowDownSeconds) {
            $slowDownSeconds = ceil($slowDownSeconds * 2.0);
            $this->deviceCodeRepository->setRetryInterval(
                $this->getIdentifier(),
                $slowDownSeconds
            );
        }

        return $slowDownSeconds;
    }
}
