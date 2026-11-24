// Cliente mínimo da Calculadora de Tributos (CBS/IBS/IS) da Receita Federal.
// Node 18+ ou Deno/Bun (usa fetch nativo). Chame do servidor, não do navegador.
// Uso: npx tsx cliente.mts

import { pathToFileURL } from "node:url";

const BASE =
  process.env.CALCULADORA_URL ??
  "https://piloto-cbs.tributos.gov.br/servico/calculadora-consumo/api/calculadora";

type EstadoItem =
  | "CALCULADO"
  | "CALCULO_SIMULADO"
  | "INCONSISTENCIA_ENTRADA"
  | "NAO_IMPLEMENTADO"
  | "FALHA_TECNICA";

interface ErroItem { code: string; title: string; detail: string; pointer?: string }
interface ObjetoItem { nObj: number; estadoItem: EstadoItem; tribCalc?: any; erros?: ErroItem[] }
interface Resposta { objetos: ObjetoItem[]; total: any }

export async function calcular(operacao: unknown, timeoutMs = 15_000) {
  const res = await fetch(`${BASE}/observabilidade/regime-geral`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(operacao),
    signal: AbortSignal.timeout(timeoutMs),
  });
  if (!res.ok) throw new Error(`Calculadora respondeu ${res.status}: ${await res.text()}`);

  const dados = (await res.json()) as Resposta;
  const simulado = res.headers.get("x-warning-dados-simulados");

  return {
    calculados: dados.objetos.filter((o) => o.estadoItem === "CALCULADO" || o.estadoItem === "CALCULO_SIMULADO"),
    comErro: dados.objetos.filter((o) => o.estadoItem === "INCONSISTENCIA_ENTRADA"),
    paraRevisar: dados.objetos.filter((o) => o.estadoItem === "NAO_IMPLEMENTADO" || o.estadoItem === "FALHA_TECNICA"),
    total: dados.total,
    aviso: simulado, // presente quando o cálculo usou alíquotas informadas (2027+)
  };
}

export async function versao() {
  const res = await fetch(`${BASE}/dados-abertos/versao`);
  return res.json() as Promise<{ versaoApp: string; versaoDb: string; dataVersaoDb: string }>;
}

// Exemplo
if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const { readFile } = await import("node:fs/promises");
  const operacao = JSON.parse(await readFile(new URL("./operacao-2026.json", import.meta.url), "utf8"));
  console.log("Versão:", await versao());
  const r = await calcular(operacao);
  for (const item of r.calculados) {
    const g = item.tribCalc?.IBSCBS?.gIBSCBS;
    console.log(`Item ${item.nObj}: ${item.estadoItem} | CBS ${g?.gCBS?.vCBS} | IBS ${g?.vIBS}`);
  }
  for (const item of r.comErro) console.log(`Item ${item.nObj}: ${item.erros?.[0]?.code} ${item.erros?.[0]?.detail}`);
  console.log("Totais:", JSON.stringify(r.total?.tribCalc?.IBSCBSTot));
}
