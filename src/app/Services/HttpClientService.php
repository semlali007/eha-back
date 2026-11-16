<?php

namespace App\Services;

use Exception;
use Core\Util\Json;
use App\Exceptions\HttpException ;
 

class HttpClientService {

    /** Hosts qui n'acceptent que TLS 1.3. */
    private const TLS13_ONLY_HOSTS = [
        'hlk-etl-api-preprod.edf.fr',
    ];

    /**
     *
     * @param string $url
     * @param array $query
     * @param array $headers
     * @return type
     * @throws Exception
     */
    public static function exec_post(string $url, array $query, array $headers = Json::DEFAULT_HEADER) {
        if (self::needsTlsStreamFallback($url)) {
            $result = self::sendViaStream('POST', $url, $query, $headers);
            if ($result === null) throw new HttpException('stream fallback failed');
            return $result;
        }

        $curl = self::init($url, $headers, $query);
        $responses = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new HttpException($error);
        }

        $json = json_decode($responses, true);
        curl_close($curl);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new HttpException('json non valide', $httpCode);
        }
        return $json;
    }

    /**
     * 
     * @param string $url
     * @param array $data
     * @return string
     */
    public static function get(string $url, array $data = []) {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        } else {
            curl_close($ch);
        }

        return $response;
    }

     /**
      * 
      * @param string $url
      * @param array $query
      * @param array $headers
      * @return mixed
      */
    static function post (string $url, array $query, array $headers = Json::DEFAULT_HEADER):mixed {
        // Bypass libcurl pour les hosts en TLS 1.3 only si la libcurl locale
        // ne sait pas faire TLS 1.3 mais que PHP ext-openssl, oui.
        if (self::needsTlsStreamFallback($url)) {
            return self::sendViaStream('POST', $url, $query, $headers);
        }

        $curl = self::init($url, $headers, $query);
        $responses = curl_exec($curl);
        $json = json_decode($responses, true);
        curl_close($curl);
        return $json ;
    }
    
    /**
     * 
     * @param string|null $url
     * @param mixed $headers
     * @param mixed $query
     * @return mixed
     */
    static function init (string|null $url, mixed $headers,mixed $query): mixed{
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($query));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($curl, CURLOPT_TCP_KEEPIDLE, 2);
        // Both were 360. A connect timeout that long pins a php-fpm worker for
        // six minutes whenever HLK is unreachable (firewalled egress drops the
        // SYN silently, so curl waits the full budget). With pm.max_children=5
        // a handful of such calls starve the pool and unrelated requests queue:
        // an /api/saml/attributes at login was observed waiting 68s, released
        // the instant an HLK call expired. max_execution_time does not help --
        // on Linux it counts CPU time, which a blocked socket does not consume.
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_VERBOSE, true);

        return $curl ;

    }

    /**
     * True si l'URL cible un host TLS 1.3 only ET que la libcurl locale ne sait pas
     * négocier TLS 1.3 ET que ext-openssl côté PHP le peut. Dans ce cas on route
     * l'appel via stream_context au lieu de libcurl.
     */
    private static function needsTlsStreamFallback(string $url): bool {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) return false;
        if (!in_array($host, self::TLS13_ONLY_HOSTS, true)) return false;
        if (defined('CURL_SSLVERSION_TLSv1_3')) return false; // libcurl OK, on l'utilise
        return defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT');
    }

    /**
     * HTTPS via stream_socket_client (ext-openssl) — fallback pour libcurl trop vieille
     * pour TLS 1.3. Supporte POST (JSON body) et GET (query en QS). Ne dépend PAS
     * de allow_url_fopen.
     */
    private static function sendViaStream(string $method, string $url, array $query, array $headers): mixed {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $port = $parts['port'] ?? 443;
        $path = $parts['path'] ?? '/';
        $existingQs = $parts['query'] ?? '';

        $body = '';
        if ($method === 'POST') {
            $body = json_encode($query);
            if ($existingQs !== '') $path .= '?' . $existingQs;
        } else { // GET
            $newQs = http_build_query($query);
            $qs = trim($existingQs . (($existingQs !== '' && $newQs !== '') ? '&' : '') . $newQs, '&');
            if ($qs !== '') $path .= '?' . $qs;
        }

        // Nettoie les headers user pour éviter les doublons.
        $cleanHeaders = [];
        foreach ($headers as $h) {
            if (!is_string($h)) continue;
            $lower = strtolower(ltrim($h));
            if (str_starts_with($lower, 'content-length:')) continue;
            if (str_starts_with($lower, 'host:')) continue;
            if (str_starts_with($lower, 'connection:')) continue;
            $cleanHeaders[] = $h;
        }

        $requestLines = ["$method $path HTTP/1.1"];
        $requestLines[] = "Host: $host";
        if ($method === 'POST') {
            $requestLines[] = 'Content-Length: ' . strlen($body);
        }
        $requestLines[] = 'Connection: close';
        foreach ($cleanHeaders as $h) $requestLines[] = $h;
        $rawRequest = implode("\r\n", $requestLines) . "\r\n\r\n" . $body;

        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
                'SNI_enabled'      => true,
                'peer_name'        => $host,
                'crypto_method'    => STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client(
            "ssl://$host:$port",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if ($stream === false) {
            error_log(sprintf(
                '[HttpClientService] sendViaStream ECHEC connexion ssl://%s:%d errno=%d errstr=%s (openssl=%s, TLSv1.3_client_const=%s)',
                $host, $port, $errno, $errstr,
                defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'ext-openssl absente',
                defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? 'defined' : 'absente'
            ));
            return null;
        }

        stream_set_timeout($stream, 30);
        fwrite($stream, $rawRequest);
        $rawResponse = '';
        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) break;
            $rawResponse .= $chunk;
        }
        fclose($stream);

        // Log si réponse > 1 MB (aide à tracer les recherches "borderline")
        if (strlen($rawResponse) > 1024 * 1024) {
            error_log(sprintf(
                "[HttpClientService] sendViaStream LARGE response url=%s size=%sMB",
                $url, round(strlen($rawResponse) / 1024 / 1024, 2)
            ));
        }

        if ($rawResponse === '') {
            error_log("[HttpClientService] sendViaStream ECHEC : connexion ssl://{$host}:{$port} OK mais reponse vide (url={$url})");
            return null;
        }

        $split = explode("\r\n\r\n", $rawResponse, 2);
        $rawHeaders = $split[0] ?? '';
        $responseBody = $split[1] ?? '';

        // Décodage transfer-encoding: chunked si présent
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (stripos($line, 'transfer-encoding:') === 0 && stripos($line, 'chunked') !== false) {
                $responseBody = self::decodeChunked($responseBody);
                break;
            }
        }

        return $responseBody !== '' ? json_decode($responseBody, true) : null;
    }

    /** Décode un body HTTP en Transfer-Encoding: chunked. */
    private static function decodeChunked(string $body): string {
        $decoded = '';
        $pos = 0;
        $len = strlen($body);
        while ($pos < $len) {
            $lineEnd = strpos($body, "\r\n", $pos);
            if ($lineEnd === false) break;
            $sizeHex = trim(substr($body, $pos, $lineEnd - $pos));
            $size = hexdec($sizeHex);
            if ($size === 0) break;
            $pos = $lineEnd + 2;
            $decoded .= substr($body, $pos, $size);
            $pos += $size + 2;
        }
        return $decoded;
    }

    public static function exec_get(string $url, array $query, array $headers = Json::DEFAULT_HEADER) {
        $headers = array_merge(Json::DEFAULT_HEADER,$headers);

        if (self::needsTlsStreamFallback($url)) {
            $result = self::sendViaStream('GET', $url, $query, $headers);
            if ($result === null) throw new HttpException('stream fallback failed');
            return $result;
        }

        $curl = self::initGet($url, $headers, $query);
        $responses = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new HttpException($error);
        }

        $json = json_decode($responses, true);
        curl_close($curl);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new HttpException('json non valide', $httpCode);
        }
        return $json;
    }

    static function initGet (string|null $url, mixed $headers,mixed $query): mixed{
        $queryUrl = http_build_query($query);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "$url?$queryUrl");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($curl, CURLOPT_TCP_KEEPIDLE, 2);
        // Both were 360. A connect timeout that long pins a php-fpm worker for
        // six minutes whenever HLK is unreachable (firewalled egress drops the
        // SYN silently, so curl waits the full budget). With pm.max_children=5
        // a handful of such calls starve the pool and unrelated requests queue:
        // an /api/saml/attributes at login was observed waiting 68s, released
        // the instant an HLK call expired. max_execution_time does not help --
        // on Linux it counts CPU time, which a blocked socket does not consume.
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_VERBOSE, true);

        return $curl ;
        
    }
}
