import logging

from captcha import CaptchaOrchestrator, CaptchaSettings


def verify_captcha(
    settings: CaptchaSettings,
    logger: logging.Logger,
    provider: str | None,
    token: str,
    remote_ip: str | None,
) -> bool:
    return CaptchaOrchestrator(settings, logger).verify(provider, token, remote_ip)
