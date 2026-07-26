from flask import Flask, jsonify
from messages import message
from werkzeug.exceptions import RequestEntityTooLarge


def register_error_handlers(app: Flask) -> None:
    @app.errorhandler(RequestEntityTooLarge)
    def request_too_large(_error):
        return jsonify(message=message("payload_too_large")), 413
