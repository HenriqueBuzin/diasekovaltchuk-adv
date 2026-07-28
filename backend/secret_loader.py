import json
import os
from pathlib import Path
from typing import Mapping

from messages import message


def load_secret_file(path: str) -> dict[str, str]:
    try:
        data = json.loads(Path(path).read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as error:
        raise RuntimeError(message("invalid_secret_file")) from error

    if not isinstance(data, dict) or any(
        not isinstance(key, str) or not isinstance(value, str) for key, value in data.items()
    ):
        raise RuntimeError(message("invalid_secret_file"))
    return data


def require_secret(name: str, environ: Mapping[str, str] | None = None) -> str:
    source = environ if environ is not None else os.environ
    secret_file = source.get("APP_SECRETS_FILE")
    value = load_secret_file(secret_file).get(name) if secret_file else source.get(name)
    if not value or not value.strip():
        raise RuntimeError(message("missing_env", name=name))
    return value.strip()
