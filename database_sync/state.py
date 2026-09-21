from __future__ import annotations

import json
import os
from pathlib import Path
from tempfile import NamedTemporaryFile
from typing import Any


def load(path: str) -> dict[str, Any]:
    state_path = Path(path)
    if not state_path.exists():
        return {"version": 1, "tables": {}}
    with state_path.open("r", encoding="utf-8") as file:
        value = json.load(file)
    if not isinstance(value, dict) or value.get("version") != 1:
        raise ValueError(f"Unsupported sync state file: {path}")
    value.setdefault("tables", {})
    return value


def save(path: str, state: dict[str, Any]) -> None:
    state_path = Path(path)
    state_path.parent.mkdir(parents=True, exist_ok=True)
    with NamedTemporaryFile("w", encoding="utf-8", dir=state_path.parent, delete=False) as file:
        json.dump(state, file, indent=2, sort_keys=True, default=str)
        file.write("\n")
        temporary = file.name
    os.replace(temporary, state_path)
