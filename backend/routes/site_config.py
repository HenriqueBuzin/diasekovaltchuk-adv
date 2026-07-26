from config import field_rules
from flask import Blueprint, current_app, jsonify

bp = Blueprint("site_config", __name__)


@bp.get("/api/site-config")
def site_config():
    return jsonify(
        contactEmail=current_app.config["CONTACT_EMAIL"],
        whatsNumber=current_app.config["WHATS_NUMBER"],
        whatsLinkNumber=current_app.config["WHATS_LINK_NUMBER"],
        socialFacebook=current_app.config["SOCIAL_FB_URL"],
        socialInstagram=current_app.config["SOCIAL_IG_URL"],
        captchaEnabled=current_app.config["CAPTCHA_ENABLED"],
        captchaProviders=[provider.public_dict() for provider in current_app.config["CAPTCHA_SETTINGS"].providers],
        turnstileSiteKey=current_app.config["TURNSTILE_SITE_KEY"],
        fieldLimits=field_rules(),
    )
