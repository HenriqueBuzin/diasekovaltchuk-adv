import os
from pathlib import Path
from typing import Mapping

from messages import message


def read_secret_file(path: str) -> str:
    try:
        return Path(path).read_text(encoding="utf-8").strip()
    except OSError as error:
        raise RuntimeError(message("invalid_secret_file")) from error


def require_secret(name: str, environ: Mapping[str, str] | None = None) -> str:
    source = environ if environ is not None else os.environ
    secret_file = source.get(f"{name}_FILE")
    value = read_secret_file(secret_file) if secret_file else source.get(name)
    if not value or not value.strip():
        raise RuntimeError(message("missing_env", name=name))
    return value.strip()
