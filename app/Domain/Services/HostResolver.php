<?php

namespace App\Domain\Services;

/**
 * Resolves a host name to a list of IP addresses.
 *
 * Extracted as a separate collaborator so the fetch guard can be tested
 * without performing real DNS lookups.
 */
class HostResolver
{
    /**
     * @return array<int, string> List of IP addresses (IPv4 and IPv6).
     */
    public function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = [];

        $ipv4 = @gethostbynamel($host);
        if (is_array($ipv4)) {
            $addresses = $ipv4;
        }

        $records = @dns_get_record($host, DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                $ipv6 = $record['ipv6'] ?? null;
                if (is_string($ipv6) && filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                    $addresses[] = $ipv6;
                }
            }
        }

        return array_values(array_unique($addresses));
    }
}
