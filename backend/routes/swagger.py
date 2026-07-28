from flask import Blueprint, abort, current_app, jsonify

bp = Blueprint("swagger", __name__)


def require_swagger() -> None:
    if not current_app.config["SWAGGER_ENABLED"]:
        abort(404)


@bp.get("/openapi.json")
def openapi():
    require_swagger()
    return jsonify(
        {
            "openapi": "3.1.0",
            "info": {
                "title": "Dias Kovaltchuk Advogadas Associadas API",
                "version": "1.0.0",
            },
            "paths": {
                "/api/site-config": {
                    "get": {
                        "summary": "Retorna a configuração pública do site",
                        "responses": {"200": {"description": "Configuração pública"}},
                    }
                },
                "/api/contact": {
                    "post": {
                        "summary": "Envia uma solicitação de contato",
                        "requestBody": {
                            "required": True,
                            "content": {
                                "application/json": {
                                    "schema": {
                                        "type": "object",
                                        "required": ["nome", "email", "telefone", "assunto", "mensagem"],
                                        "properties": {
                                            "nome": {"type": "string"},
                                            "email": {"type": "string", "format": "email"},
                                            "telefone": {"type": "string"},
                                            "assunto": {"type": "string"},
                                            "mensagem": {"type": "string"},
                                            "captchaProvider": {"type": "string"},
                                            "captchaToken": {"type": "string"},
                                        },
                                    }
                                }
                            },
                        },
                        "responses": {
                            "200": {"description": "Contato enviado"},
                            "400": {"description": "Dados inválidos"},
                            "502": {"description": "Falha no envio"},
                        },
                    }
                },
            },
        }
    )


@bp.get("/docs")
def swagger_ui():
    require_swagger()
    return (
        """<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dias Kovaltchuk API</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>SwaggerUIBundle({url: "/openapi.json", dom_id: "#swagger-ui"});</script>
</body>
</html>""",
        200,
        {"Content-Type": "text/html; charset=utf-8"},
    )
