"""Cliente mínimo da Calculadora de Tributos (CBS/IBS/IS) da Receita Federal.

Só usa a biblioteca padrão (Python 3.9+). Uso: python cliente.py
"""
import json
import os
import urllib.request
from pathlib import Path

BASE = os.environ.get(
    "CALCULADORA_URL",
    "https://piloto-cbs.tributos.gov.br/servico/calculadora-consumo/api/calculadora",
)


def _post(caminho: str, corpo: dict, timeout: int = 15):
    req = urllib.request.Request(
        BASE + caminho,
        data=json.dumps(corpo).encode(),
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        return json.load(resp), resp.headers.get("x-warning-dados-simulados")


def versao() -> dict:
    with urllib.request.urlopen(BASE + "/dados-abertos/versao", timeout=15) as resp:
        return json.load(resp)


def calcular(operacao: dict) -> dict:
    """Calcula item por item. HTTP 200 não garante que todos os itens foram calculados."""
    dados, aviso = _post("/observabilidade/regime-geral", operacao)
    objs = dados["objetos"]
    return {
        "calculados": [o for o in objs if o["estadoItem"] in ("CALCULADO", "CALCULO_SIMULADO")],
        "com_erro": [o for o in objs if o["estadoItem"] == "INCONSISTENCIA_ENTRADA"],
        "para_revisar": [o for o in objs if o["estadoItem"] in ("NAO_IMPLEMENTADO", "FALHA_TECNICA")],
        "total": dados.get("total"),
        "aviso": aviso,
    }


if __name__ == "__main__":
    operacao = json.loads((Path(__file__).parent / "operacao-2026.json").read_text(encoding="utf-8"))
    print("Versão:", versao())
    r = calcular(operacao)
    for item in r["calculados"]:
        g = item["tribCalc"]["IBSCBS"]["gIBSCBS"]
        print(f"Item {item['nObj']}: {item['estadoItem']} | CBS {g['gCBS']['vCBS']} | IBS {g['vIBS']}")
    for item in r["com_erro"]:
        e = item["erros"][0]
        print(f"Item {item['nObj']}: {e['code']} {e['detail']}")
    print("Totais:", json.dumps(r["total"]["tribCalc"]["IBSCBSTot"], ensure_ascii=False))
