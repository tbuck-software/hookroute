<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class UrlGuard
{
    public function assertSafe(string $url): void
    {
        $this->connectionOptions($url);
    }

    public function connectionOptions(string $url): array
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? null) !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            $this->reject();
        }

        if (config('hookroute.allow_private_destinations')) {
            return [];
        }

        $host = $parts['host'];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses = [$host];
        } else {
            $addresses = collect($this->resolve($host));
            if (($system = $this->systemAddress($host)) !== null) {
                $addresses->push($system);
            }
            $addresses = $addresses->unique()->values()->all();
        }

        if ($addresses === [] || collect($addresses)->contains(fn (string $ip) => ! $this->isPublicIp($ip))) {
            $this->reject();
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [];
        }

        $port = (int) ($parts['port'] ?? 443);
        $address = $addresses[0];
        if (str_contains($address, ':')) {
            $address = '['.$address.']';
        }

        return [
            'curl' => [CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, $port, $address)]],
        ];
    }

    protected function resolve(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        return collect($records ?: [])
            ->map(fn (array $record) => $record['ip'] ?? $record['ipv6'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function systemAddress(string $host): ?string
    {
        // cURL resolves through the system resolver (/etc/hosts, container
        // aliases), which can disagree with the DNS records above. Its
        // answer must pass the same safety check as every other address.
        $address = gethostbyname($host);

        return $address === $host ? null : $address;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function reject(): never
    {
        throw ValidationException::withMessages([
            'url' => 'Destination URLs must use HTTPS and resolve only to public network addresses.',
        ]);
    }
}
