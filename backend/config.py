import os
from pathlib import Path

from captcha import CaptchaSettings, load_captcha_settings
from contact import FIELD_LIMITS, only_digits
from messages import message
from secret_loader import require_secret

ROOT = Path(__file__).resolve().parents[1]


def require_env(name: str) -> str:
    value = os.getenv(name)
    if not value:
        raise RuntimeError(message("missing_env", name=name))
    return value


def parse_bool_env(name: str) -> bool:
    return require_env(name).strip().lower() in ("1", "true", "t", "yes", "y")


def optional_bool_env(name: str, default: bool) -> bool:
    value = os.getenv(name)
    return default if value is None else value.strip().lower() in ("1", "true", "t", "yes", "y")


def parse_recipients(value: str) -> list[str]:
    recipients = [email.strip() for email in value.split(",") if email.strip()]
    if not recipients:
        raise RuntimeError(message("missing_contact_to"))
    return recipients


def turnstile_provider(settings: CaptchaSettings):
    return next((provider for provider in settings.providers if provider.name == "turnstile"), None)


def load_turnstile_config(enabled: bool) -> tuple[str, str]:
    turnstile = turnstile_provider(load_captcha_settings(enabled=enabled))
    return (turnstile.site_key, turnstile.secret_key) if turnstile else ("", "")


def field_rules() -> dict[str, dict[str, int]]:
    return {name: {"min": limits[0], "max": limits[1]} for name, limits in FIELD_LIMITS.items()}


def environment_config() -> dict:
    captcha_enabled = parse_bool_env("CAPTCHA_ENABLED")
    captcha_settings = load_captcha_settings(enabled=captcha_enabled)
    turnstile = turnstile_provider(captcha_settings)
    whats_number = require_env("WHATS_NUMBER")

    return {
        "DEBUG": parse_bool_env("FLASK_DEBUG"),
        "SECRET_KEY": require_secret("FLASK_SECRET_KEY"),
        "PREFERRED_URL_SCHEME": "https",
        "MAX_CONTENT_LENGTH": 64 * 1024,
        "MAIL_SERVER": require_env("MAIL_SERVER"),
        "MAIL_PORT": int(require_env("MAIL_PORT")),
        "MAIL_USE_TLS": parse_bool_env("MAIL_USE_TLS"),
        "MAIL_USE_SSL": parse_bool_env("MAIL_USE_SSL"),
        "MAIL_USERNAME": require_env("MAIL_USERNAME"),
        "MAIL_PASSWORD": require_secret("MAIL_PASSWORD"),
        "MAIL_DEFAULT_SENDER": require_env("MAIL_USERNAME"),
        "CONTACT_EMAIL": require_env("CONTACT_EMAIL"),
        "WHATS_NUMBER": whats_number,
        "WHATS_LINK_NUMBER": only_digits(whats_number),
        "SOCIAL_FB_URL": require_env("SOCIAL_FB_URL"),
        "SOCIAL_IG_URL": require_env("SOCIAL_IG_URL"),
        "EMAIL_DNS_VALIDATION_ENABLED": optional_bool_env("EMAIL_DNS_VALIDATION_ENABLED", True),
        "CAPTCHA_ENABLED": captcha_enabled,
        "CAPTCHA_SETTINGS": captcha_settings,
        "TURNSTILE_SITE_KEY": turnstile.site_key if turnstile else "",
        "RECIPIENTS": parse_recipients(require_env("CONTACT_TO")),
        "FRONTEND_DIST": Path(os.getenv("FRONTEND_DIST", ROOT / "frontend" / "dist")),
    }
