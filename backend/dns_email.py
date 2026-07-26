from typing import Callable, Iterable

import dns.resolver
import requests

Resolver = Callable[[str, str], Iterable[object]]
DOH_URL = "https://cloudflare-dns.com/dns-query"


def email_domain(email: str) -> str:
    parts = email.strip().rsplit("@", 1)
    if len(parts) != 2 or not parts[0] or not parts[1]:
        raise ValueError("Informe um e-mail válido.")
    return parts[1].lower()


def record_text(record: object) -> str:
    return str(record).strip('"')


def resolve_mx_doh(domain: str) -> tuple[str, ...]:
    try:
        response = requests.get(
            DOH_URL,
            params={"name": domain, "type": "MX"},
            headers={"accept": "application/dns-json"},
            timeout=5,
        )
        response.raise_for_status()
        data = response.json()
    except requests.RequestException, ValueError:
        return ()

    return tuple(answer["data"].strip('"') for answer in data.get("Answer", ()) if "data" in answer)


def resolve_mx(
    domain: str,
    resolver: Resolver = dns.resolver.resolve,
    use_doh: bool = True,
) -> tuple[str, ...]:
    try:
        records = tuple(record_text(record) for record in resolver(domain, "MX"))
    except dns.resolver.NXDOMAIN, dns.resolver.NoAnswer, dns.resolver.NoNameservers, dns.resolver.Timeout:
        records = ()
    return records or (resolve_mx_doh(domain) if use_doh else ())


def mx_record_accepts_mail(record: str) -> bool:
    host = record.rsplit(maxsplit=1)[-1].strip().rstrip(".")
    return bool(host)


def email_domain_accepts_mail(
    email: str,
    resolver: Resolver = dns.resolver.resolve,
    use_doh: bool = True,
) -> bool:
    domain = email_domain(email)
    return any(mx_record_accepts_mail(record) for record in resolve_mx(domain, resolver, use_doh))
