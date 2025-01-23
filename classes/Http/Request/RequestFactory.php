<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http\Request;

use Dizions\Unclogged\Http\IpAddressRange;
use Dizions\Unclogged\Setup\Environment;
use Laminas\Diactoros\ServerRequestFactory;

class RequestFactory
{
    private array $trustedProxies;

    public function __construct(Environment $env)
    {
        $this->trustedProxies = $env->get('TRUSTED_PROXIES') ?? [];
    }

    /**
     * Create a Request object with its server info adjusted to hide the details of any proxies.
     *
     * This will check if REMOTE_ADDR is found in one of the CIDR-ranges specified in
     * TRUSTED_PROXIES. If so, it will follow X-Forwarded-For backwards until it finds an untrusted
     * address, and use that as the new REMOTE_ADDR. If X-Forwarded-For is not set, but X-Real-IP
     * is, that will be used instead.
     */
    public function fromGlobals(
        array $server = null,
        array $get = null,
        array $post = null,
        array $cookie = null,
        array $files = null
    ): Request {
        if ($server === null) {
            $server = ServerRequestFactory::fromGlobals()->getServerParams();
        }
        $server = $this->adjustRemoteAddress($server);
        $serverRequest = ServerRequestFactory::fromGlobals($server, $get, $post, $cookie, $files);
        return new Request($serverRequest);
    }

    /** @deprecated */
    public function proxiedRequestFromGlobals(
        array $server = null,
        array $get = null,
        array $post = null,
        array $cookie = null,
        array $files = null
    ): Request {
        return $this->fromGlobals($server, $get, $post, $cookie, $files);
    }

    private function adjustRemoteAddress(array $serverParams): array
    {
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '';
        if ($remoteAddr && $this->isTrustedProxy($remoteAddr)) {
            $remoteAddr = $this->getAddressFromProxyHeaders($serverParams) ?? $remoteAddr;
            unset($serverParams['HTTP_X_FORWARDED_FOR']);
            unset($serverParams['HTTP_X_REAL_IP']);
            $serverParams['REMOTE_ADDR'] = $remoteAddr;
        }
        return $serverParams;
    }

    private function isTrustedProxy($address): bool
    {
        foreach ($this->trustedProxies as $proxy) {
            if ((new IpAddressRange($proxy))->contains($address)) {
                return true;
            }
        }
        return false;
    }

    private function getAddressFromProxyHeaders(array $serverParams): ?string
    {
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            // There could be multiple hops; get the address of the most recent one, which is the
            // address used to connect to our closest proxy
            $addresses = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            do {
                $remoteAddr = trim(array_pop($addresses));
            } while (count($addresses) && self::isTrustedProxy($remoteAddr));
        } elseif (!empty($serverParams['HTTP_X_REAL_IP'])) {
            $remoteAddr = $serverParams['HTTP_X_REAL_IP'];
        } else {
            $remoteAddr = null;
        }
        return $remoteAddr;
    }
}
