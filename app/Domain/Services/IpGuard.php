<?php

namespace App\Domain\Services;

/**
 * Decides whether an IP address is publicly routable.
 *
 * Used to keep the image fetcher from being turned into an SSRF probe against
 * loopback, private networks, link-local metadata services and other
 * non-public ranges.
 */
class IpGuard
{
    /**
     * Private / reserved ranges that FILTER_FLAG_NO_PRIV_RANGE and
     * FILTER_FLAG_NO_RES_RANGE do not cover.
     *
     * @var array<int, array{0: string, 1: int}>
     */
    private const EXTRA_BLOCKED_CIDRS = [
        ['0.0.0.0', 8],          // "this network"
        ['100.64.0.0', 10],      // carrier-grade NAT
        ['192.0.0.0', 24],       // IETF protocol assignments
        ['192.0.2.0', 24],       // TEST-NET-1
        ['198.18.0.0', 15],      // benchmarking
        ['198.51.100.0', 24],    // TEST-NET-2
        ['203.0.113.0', 24],     // TEST-NET-3
        ['240.0.0.0', 4],        // reserved / broadcast
        ['2001:db8::', 32],      // documentation
    ];

    public function isPublic(string $address): bool
    {
        // An IPv4-mapped IPv6 address (::ffff:a.b.c.d) is judged by the IPv4
        // value it embeds. This must happen before filter_var(), because PHP
        // treats the whole ::ffff:0:0/96 block as reserved.
        $mapped = $this->mappedIpv4($address);

        if ($mapped !== null) {
            return $this->isPublic($mapped);
        }

        $public = filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($public === false) {
            return false;
        }

        foreach (self::EXTRA_BLOCKED_CIDRS as [$subnet, $bits]) {
            if ($this->inCidr($address, $subnet, $bits)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract the embedded IPv4 address from an IPv4-mapped IPv6 address.
     */
    private function mappedIpv4(string $address): ?string
    {
        if (! str_contains($address, '.') || ! str_contains($address, ':')) {
            return null;
        }

        $tail = substr($address, strrpos($address, ':') + 1);

        return filter_var($tail, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? $tail : null;
    }

    private function inCidr(string $address, string $subnet, int $bits): bool
    {
        $addressBytes = @inet_pton($address);
        $subnetBytes = @inet_pton($subnet);

        if ($addressBytes === false || $subnetBytes === false || strlen($addressBytes) !== strlen($subnetBytes)) {
            return false;
        }

        $wholeBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        if ($wholeBytes > 0 && strncmp($addressBytes, $subnetBytes, $wholeBytes) !== 0) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $remainingBits) & 0xFF;

        return (ord($addressBytes[$wholeBytes]) & $mask) === (ord($subnetBytes[$wholeBytes]) & $mask);
    }
}
