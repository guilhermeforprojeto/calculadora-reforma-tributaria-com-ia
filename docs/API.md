# API da calculadora (regime regular)

Conferido na versão **1.7.2** (base de dados `V0073`, de 09/11/2026). Para a versão atual: `GET /dados-abertos/versao`. Lista completa, com esquemas e exemplos, no Swagger da instância local: `http://localhost:8080/api/swagger-ui.html`.

## Bases

| Onde | Base |
| --- | --- |
| Online (piloto da Receita, sem chave) | `https://piloto-cbs.tributos.gov.br/servico/calculadora-consumo/api/calculadora` |
| Local (perfil `offline`) | `http://localhost:8080/api/calculadora` |

Todos os caminhos abaixo vêm depois da base.

## Cálculo

| Método e caminho | O que faz |
| --- | --- |
| `POST /regime-geral` | Calcula a operação inteira. O primeiro item com erro derruba tudo (HTTP 422) |
| `POST /observabilidade/regime-geral` | Calcula item por item; itens com erro voltam com `estadoItem` e `erros`, sem parar os outros (HTTP 200) |
| `GET /observabilidade/erros?codigo=REG-011` | Explica um código de erro |
| `POST /base-calculo/base-calculo-unificada` | Base de cálculo da NF-e/NFC-e a partir de `vProd`, frete, seguro, descontos, ICMS etc., aplicando a vigência de cada componente no ano |
| `POST /base-calculo/base-calculo-unificada-nfse` | O mesmo para NFS-e |

### Entrada mínima

```json
{
  "id": "pedido-123",
  "versao": "1.0.0",
  "dhFatoGerador": "2026-06-10T10:00:00-03:00",
  "municipio": 4314902,
  "uf": "RS",
  "itens": [
    { "numero": 1, "ncm": "10063021", "cst": "200", "cClassTrib": "200003", "baseCalculo": 100.00, "quantidade": 20, "unidade": "KG" },
    { "numero": 2, "nbs": "122011200", "cst": "200", "cClassTrib": "200028", "baseCalculo": 1200.00 }
  ]
}
```

| Campo | O que é |
| --- | --- |
| `dhFatoGerador` | Data e hora do fato gerador. Define as regras e alíquotas aplicadas. (`dataHoraEmissao` está obsoleto) |
| `municipio` | Código IBGE de 7 dígitos |
| `ncm` / `nbs` | NCM para mercadoria, NBS para serviço |
| `cst` / `cClassTrib` | Situação tributária e classificação tributária do item (tabelas em `dados-abertos`) |
| `aliquotasNominais` | **Proibido até 2026, obrigatório a partir de 2027**: `{ "cbs": 8.8, "ibsEstadual": 0.05, "ibsMunicipal": 0.05 }` |
| `impostoSeletivo` | Grupo do IS para itens sujeitos a ele (bebidas, cigarros etc.) |

### Saída

Cada item volta em `objetos[]` com `tribCalc.IBSCBS.gIBSCBS` (base `vBC`, alíquotas `pCBS`/`pIBSUF`/`pIBSMun`, reduções em `gRed`, valores `vCBS`/`vIBS`) e uma `memoriaCalculo` em texto explicando a regra aplicada. Os totais ficam em `total.tribCalc.IBSCBSTot`.

Exemplo real (API online, 24/11/2026, ano de teste 2026): serviço de educação com base 1.200,00 e redução de 60% → CBS 0,9% × 40% = **R$ 4,32** e IBS estadual 0,1% × 40% = **R$ 0,48**.

### Estados do item (`estadoItem`)

| Estado | O que fazer |
| --- | --- |
| `CALCULADO` | Usar o valor |
| `CALCULO_SIMULADO` | Calculado com alíquotas informadas (`aliquotasNominais`). Header `x-warning-dados-simulados` diz o motivo. Mostrar como simulação |
| `INCONSISTENCIA_ENTRADA` | Erro nos dados (ex.: `REG-011` NCM não vinculada ao cClassTrib). Corrigir a entrada; o campo `pointer` diz qual (`#/itens/3/ncm`) |
| `NAO_IMPLEMENTADO` | A calculadora ainda não trata esse caso. Não inventar valor: sinalizar para revisão humana |
| `FALHA_TECNICA` | Erro interno. Tentar de novo depois; se persistir, reportar à Receita |

Os totais somam só os itens calculados.

### Erros (`/regime-geral`)

Formato RFC 9457 (`application/problem+json`):

```json
{ "status": 422, "title": "NCM não vinculada", "detail": "NCM de código 02013000 não vinculada à Classificação Tributária de código 200035 (CBS e IBS)", "type": "https://piloto-cbs.tributos.gov.br/errors/ncm-nao-vinculada" }
```

## XML dos documentos fiscais

| Método e caminho | O que faz |
| --- | --- |
| `POST /xml/generate?tipo=nfe` | Gera o grupo de tributação em XML a partir da resposta de `POST /regime-geral` |
| `POST /xml/validate?tipo=nfe&subtipo=grupo` | Valida contra o XSD (`subtipo=grupo` ou `nota`); 422 traz linha e coluna do erro |
| `GET /xml/generate`, `GET /xml/validate` | Tipos de documento aceitos |

## Dados abertos (`/dados-abertos/...`)

Quase todos aceitam `?data=AAAA-MM-DD` para pegar a tabela vigente naquela data.

| Caminho | Conteúdo |
| --- | --- |
| `/versao` | Versão da aplicação e da base de dados |
| `/situacoes-tributarias/cbs-ibs`, `/situacoes-tributarias/imposto-seletivo` | CST |
| `/classificacoes-tributarias/cbs-ibs`, `/classificacoes-tributarias/cbs-ibs/{cst}`, `/classificacoes-tributarias/cbs-ibs/class-trib/{cClassTrib}` | cClassTrib |
| `/classificacoes-tributarias/ncm-aplicavel`, `/classificacoes-tributarias/nbs-aplicavel` | Quais cClassTrib valem para uma NCM/NBS |
| `/ncm?ncm=22030000`, `/ncm/vigentes`, `/nbs`, `/nbs/lista` | NCM e NBS |
| `/fundamentacoes-legais` | Artigos da LC 214/2025 por classificação |
| `/aliquota-uniao`, `/aliquota-uf?codigoUf=43`, `/aliquota-municipio` | Alíquotas de referência (só 2026; a partir de 2027 retorna 404) |
| `/ufs`, `/ufs/municipios` | UFs e municípios (código IBGE) |

## Instância local

| Caminho | O que faz |
| --- | --- |
| `GET http://localhost:8080/api/versao/status` | Compara a versão local com a online e diz se precisa atualizar (só perfil `offline`) |

## Outros projetos da Receita

| Repositório | Porta padrão | Para quê |
| --- | --- | --- |
| [calculadora-rtc-simples-nacional](https://github.com/ReceitaFederal/calculadora-rtc-simples-nacional) | 8082 | CBS e IBS de optantes do Simples Nacional: `POST /api/calculadora/observabilidade/simples-nacional`. Usa o banco gerado com o Flyway do regime regular |
| [calculadora-rtc-gerador-danfse](https://github.com/ReceitaFederal/calculadora-rtc-gerador-danfse) | 8080 (`SERVER_PORT`) | Serviço em Go que gera o PDF do DANFSe a partir do XML da NFS-e; tem Dockerfile |
