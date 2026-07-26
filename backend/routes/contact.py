from smtplib import SMTPException

from contact import Contato, validate_contact
from dns_email import email_domain_accepts_mail
from flask import Blueprint, current_app, jsonify, request
from messages import message
from services.captcha_service import verify_captcha
from services.contact_mailer import send_contact_email

bp = Blueprint("contact", __name__)


def client_ip() -> str | None:
    return (
        request.headers.get("CF-Connecting-IP")
        or request.headers.get("X-Forwarded-For", "").split(",")[0].strip()
        or request.remote_addr
    )


@bp.post("/api/contact")
def send_contact():
    data = request.get_json(silent=True) or request.form
    contact = Contato.from_mapping(data)

    if data.get("website"):
        current_app.logger.warning("Honeypot acionado no formulário de contato.")
        return jsonify(message=message("honeypot")), 400

    validation_errors = validate_contact(contact)
    if validation_errors:
        return jsonify(message=" ".join(validation_errors), errors=validation_errors), 400

    if current_app.config["EMAIL_DNS_VALIDATION_ENABLED"] and not email_domain_accepts_mail(contact.email):
        dns_error = message("email_domain_unavailable")
        return jsonify(message=dns_error, errors=[dns_error]), 400

    if current_app.config["CAPTCHA_ENABLED"] and not verify_captcha(
        current_app.config["CAPTCHA_SETTINGS"],
        current_app.logger,
        data.get("captchaProvider"),
        data.get("captchaToken", ""),
        client_ip(),
    ):
        return jsonify(message=message("captcha_failed")), 400

    try:
        send_contact_email(contact)
    except SMTPException:
        current_app.logger.exception("Erro ao enviar e-mail")
        return jsonify(message=message("contact_failed")), 502
    except Exception:
        current_app.logger.exception("Erro inesperado ao enviar e-mail")
        return jsonify(message=message("contact_failed")), 500

    return jsonify(message=message("contact_success"), conversion=True)
