import os

from config import environment_config
from extensions import mail
from flask import Flask
from routes.contact import bp as contact_bp
from routes.errors import register_error_handlers
from routes.frontend import bp as frontend_bp
from routes.site_config import bp as site_config_bp
from routes.swagger import bp as swagger_bp
from werkzeug.middleware.proxy_fix import ProxyFix


def register_routes(app: Flask) -> None:
    app.register_blueprint(site_config_bp)
    app.register_blueprint(contact_bp)
    app.register_blueprint(swagger_bp)
    app.register_blueprint(frontend_bp)
    register_error_handlers(app)


def create_app(config: dict | None = None) -> Flask:
    application = Flask(__name__)
    application.config.from_mapping(environment_config())
    if config:
        application.config.update(config)

    application.wsgi_app = ProxyFix(application.wsgi_app, x_for=1, x_proto=1, x_host=1, x_port=1)
    mail.init_app(application)
    register_routes(application)
    return application


app = create_app()


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=int(os.getenv("PORT", 5000)))
