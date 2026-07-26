from contact import Contato, build_message_body
from extensions import mail
from flask import current_app
from flask_mail import Message


def send_contact_email(contact: Contato) -> None:
    email_message = Message(
        subject=contact.assunto,
        recipients=current_app.config["RECIPIENTS"],
        reply_to=contact.email,
        body=build_message_body(contact),
    )
    mail.send(email_message)
