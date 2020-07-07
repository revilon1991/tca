<?php

declare(strict_types=1);

namespace App\Component\JwtToken\Handler;

use App\Component\JwtToken\Exception\JwtTokenException;
use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;

class JwtTokenHandler
{
    /**
     * @var string
     */
    private $audience;

    /**
     * @var int
     */
    private $expiration;

    /**
     * @var string
     */
    private $issuer;

    /**
     * @var int
     */
    private $notBefore;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var string
     */
    private $signature;

    /**
     * @var Signer
     */
    private $signer;

    /**
     * @param Signer $signer
     * @param string $signature
     * @param string $issuer
     * @param string|null $audience
     * @param int $notBefore
     * @param int $expiration
     */
    public function __construct(
        Signer $signer,
        string $signature,
        string $issuer,
        ?string $audience,
        int $notBefore = 0,
        int $expiration = 3600
    ) {
        $this->audience = $audience;
        $this->expiration = $expiration;
        $this->issuer = $issuer;
        $this->notBefore = $notBefore;
        $this->parser = new Parser();
        $this->signature = $signature;
        $this->signer = $signer;
    }

    /**
     * @param string $tokenString
     *
     * @return array
     *
     * @throws JwtTokenException
     */
    public function decode(string $tokenString): array
    {
        $verifyResult = null;

        try {
            $jwtToken = $this->parser->parse($tokenString);
        } catch (Exception $exception) {
            throw JwtTokenException::parseFailed($exception->getMessage());
        }

        $this->validate($jwtToken);

        $params = array_merge($jwtToken->getClaims(), $jwtToken->getHeaders());

        foreach ($params as &$jwtValue) {
            if ($jwtValue instanceof Basic) {
                $jwtValue = $jwtValue->getValue();
            }
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(array $tokenParameterList, ?string $tokenId = null): string
    {
        $builder = $this->getBuilder();

        if ($tokenId) {
            $builder->identifiedBy($tokenId);
        }

        foreach ($tokenParameterList as $key => $value) {
            $builder->withClaim($key, $value);
        }

        $builder->getToken($this->signer, new Key($this->signature));

        return (string)$builder->getToken();
    }

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        $issuedAt = time();

        $builder = new Builder();
        $builder
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->issuedAt($issuedAt)
            ->canOnlyBeUsedAfter($issuedAt + $this->notBefore)
            ->expiresAt($issuedAt + $this->expiration)
        ;

        return $builder;
    }

    /**
     * @param Token $jwtToken
     *
     * @throws JwtTokenException
     */
    private function validate(Token $jwtToken): void
    {
        try {
            $verifyResult = $jwtToken->verify($this->signer, $this->signature);
        } catch (Exception $exception) {
            throw JwtTokenException::verifyFailed($exception->getMessage());
        }

        if (!$verifyResult) {
            throw JwtTokenException::verifyFailed('Empty result');
        }

        $isExpired = $jwtToken->isExpired();

        if ($isExpired) {
            throw JwtTokenException::tokenExpired();
        }

        if (!$jwtToken->validate(new ValidationData())) {
            throw JwtTokenException::tokenNotReady();
        }

        $validationData = new ValidationData();
        $validationData->setIssuer($this->issuer);
        $validationResult = $jwtToken->validate($validationData);

        if (!$validationResult) {
            throw JwtTokenException::tokenInvalid('Issuer');
        }

        $validationData = new ValidationData();
        $validationData->setAudience($this->audience);
        $validationResult = $jwtToken->validate($validationData);

        if (!$validationResult) {
            throw JwtTokenException::tokenInvalid('Audience');
        }
    }
}
