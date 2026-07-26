from pathlib import Path

from flask import Blueprint, abort, current_app, jsonify, send_from_directory
from messages import message

bp = Blueprint("frontend", __name__)


@bp.route("/", defaults={"path": ""})
@bp.route("/<path:path>")
def frontend(path: str):
    if path.startswith("api/"):
        abort(404)

    dist = Path(current_app.config["FRONTEND_DIST"])
    requested = dist / path
    if path and requested.is_file():
        return send_from_directory(dist, path)
    if (dist / "index.html").is_file():
        return send_from_directory(dist, "index.html")
    return jsonify(message=message("frontend_missing")), 503
