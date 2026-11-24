#!/usr/bin/env bash
# Calcula a operação de exemplo na calculadora da Receita.
# Uso: bash exemplos/curl.sh                                   (API online, piloto)
#      BASE=http://localhost:8080/api/calculadora bash exemplos/curl.sh   (instância local)
set -euo pipefail
BASE="${BASE:-https://piloto-cbs.tributos.gov.br/servico/calculadora-consumo/api/calculadora}"
DIR="$(cd "$(dirname "$0")" && pwd)"

echo "== Versão"
curl -sS "$BASE/dados-abertos/versao"; echo

echo "== Cálculo com observabilidade (item 3 tem erro de propósito)"
curl -sS -X POST "$BASE/observabilidade/regime-geral" \
  -H 'Content-Type: application/json' \
  --data-binary @"$DIR/operacao-2026.json"
echo
# Resultado esperado (v1.7.2, 2026):
#   item 1 CALCULADO (arroz, redução de 100%: CBS e IBS zero)
#   item 2 CALCULADO (educação, redução de 60%: vCBS 4.32, vIBS 0.48)
#   item 3 INCONSISTENCIA_ENTRADA, erro REG-011 (NCM não vinculada ao cClassTrib)
