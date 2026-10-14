<?php

namespace App\Services;

use Firebase\JWT\{
    JWK,
    JWT,
    BeforeValidException,
    ExpiredException,
    SignatureInvalidException
};
use App\Exceptions\InvalidTokenException;
use App\Services\HttpClientService as client;
use Core\Http\Response;
use Core\Cache\{
    ApcuCachePool as cache,
    Common\PhpCacheItem
};

class JwtTokenService {

    /** @var string */
    public const ALGORITHM = 'RS256';

    /** @var string * */
    public const CERTKEY = "WEBSSO_CERT";

    /** @var array */
    private $options;

    /**
     * Constructor
     */
    function __construct(array $options = []) {
        $this->options = $options;
    }

    /**
     * @return object
     *
     * @throws InvalidTokenException
     */
    public function decodeToken(string $token) {
        try {

            if (!cache::isExpired(self::CERTKEY)) { // If Cache Expired 
                $certificate = cache::fetchObjectFromCache(self::CERTKEY);
            } else {
                // 1-  jwksURL / cache => Ref user , token (role associés)
                $jwksUrl = getenv("URL_GARDIAN_WEBSSO") . getenv("PATH_JWKS");
                // 1- appel jwks file 
                $certificate = client::get($jwksUrl); //Cache a configurer avec TTL
                // 2- utlisation direct du jwks (pour chaque appel)
                $certObject = new PhpCacheItem(self::CERTKEY, $certificate);
                cache::storeItemInCache($certObject);
            }

            // firebase/php-jwt v6 removed the third $allowed_algs argument of
            // JWT::decode(): the Key objects returned by JWK::parseKeySet()
            // carry their own algorithm. Passing ALGORITHM as parseKeySet()'s
            // $defaultAlg keeps the RS256 intent explicit and still works when
            // a JWKS entry omits "alg".
            return JWT::decode($token, JWK::parseKeySet(json_decode($certificate, true), self::ALGORITHM));

            // Catch Exceptions  
        } catch (SignatureInvalidException $e) {
            //  signature invalide
            http_response_code(Response::HTTP_BAD_REQUEST);   // redirection a la partie Front=> 400 Error 
            throw new SignatureInvalidException('Invalid signature exception');
        } catch (BeforeValidException $e) {
            // Token invalid
            http_response_code(Response::HTTP_BAD_REQUEST);   // redirection a la partie Front=> 400 Error 
            throw new BeforeValidException("Token invalid");
        } catch (ExpiredException $e) {
            // Token expiré
            http_response_code(Response::HTTP_GONE);   // redirection a la partie Front=> 410 Error 
            throw new BeforeValidException("Token est déja Expiré");
        }
    }

}
