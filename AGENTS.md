# AGENTS.md — calculadora da Reforma Tributária com IA

Você (IA) recebeu o link deste repositório. Ele explica como usar a **Calculadora de Tributos da Reforma Tributária do Consumo** (CBS, IBS e Imposto Seletivo), que a Receita Federal publicou como código aberto (licença MIT) em [github.com/ReceitaFederal](https://github.com/ReceitaFederal), para calcular esses tributos dentro de um sistema.

Responda no idioma do usuário.

## Primeiro, descubra o objetivo

Pergunte uma vez, só se o pedido não deixar claro:

| O usuário quer… | Caminho |
| --- | --- |
| Testar ou entender a calculadora | **A. API online** (sem instalar nada) |
| Rodar a calculadora na própria máquina ou servidor | **B. Rodar local** |
| Calcular os tributos dentro de um sistema que já existe (ERP, loja, emissor de nota, planilha) | **C. Integrar** |
| Criar um produto em cima da calculadora | **D. Produto** (C + [`docs/IDEIAS-DE-PRODUTO.md`](docs/IDEIAS-DE-PRODUTO.md)) |

## A. API online

- Base: `https://piloto-cbs.tributos.gov.br/servico/calculadora-consumo/api/calculadora`
- Sem chave de API. Ambiente **piloto**: sem garantia de disponibilidade, serve para testes.
- Endpoints, campos e erros em [`docs/API.md`](docs/API.md). Exemplo pronto: [`exemplos/operacao-2026.json`](exemplos/operacao-2026.json) e [`exemplos/curl.sh`](exemplos/curl.sh).
- Chame **do servidor ou do terminal**, não do navegador de um site seu: não conte com CORS liberado.

## B. Rodar local

Siga [`docs/RODAR-LOCAL.md`](docs/RODAR-LOCAL.md). Resumo: Java 25, clonar `ReceitaFederal/calculadora-rtc-regime-regular`, gerar o banco SQLite com o Flyway (obrigatório), subir com o perfil `offline` na porta 8080. Peça permissão antes de instalar o Java ou clonar.

## C. Integrar

1. **Escolha onde a calculadora roda.** Produção = instância local/própria (passo B), na versão fixada. A API online fica para testes.
2. **Monte a operação a partir dos dados do sistema** (item, NCM ou NBS, CST, cClassTrib, base de cálculo, município IBGE, UF, data do fato gerador). Não invente CST nem cClassTrib: consulte `dados-abertos` (veja `docs/API.md`) ou peça ao usuário/contador.
3. **Use o endpoint com observabilidade** (`/observabilidade/regime-geral`) para lotes: ele calcula item por item e devolve `estadoItem` em cada um. Trate todos os estados (tabela em `docs/API.md`). Um HTTP 200 **não** quer dizer que tudo foi calculado.
4. **Guarde junto do resultado** a versão da calculadora (`/dados-abertos/versao`), a data do fato gerador e a `memoriaCalculo`. É o que permite explicar um valor depois.
5. **Se o sistema emite nota**, gere o grupo XML com `/xml/generate` e valide com `/xml/validate` antes de enviar para autorização.
6. **Teste com casos conhecidos**, incluindo os do [`exemplos/`](exemplos) e um item com erro de propósito, comparando com a API online na mesma versão.
7. Clientes prontos para adaptar: [`exemplos/cliente.mts`](exemplos/cliente.mts), [`exemplos/cliente.py`](exemplos/cliente.py), [`exemplos/cliente.php`](exemplos/cliente.php).

## Regras

- **Nunca reimplemente as regras de cálculo** (reduções, alíquotas, crédito, arredondamento) no código do usuário. A calculadora existe para isso; copiar a regra cria divergência na próxima versão.
- **Não chute dado tributário.** CST, cClassTrib, NCM, NBS e alíquotas vêm da calculadora (dados abertos) ou do usuário. Se faltar, pergunte.
- **Datas importam.** Em 2026 (ano de teste) a calculadora usa as alíquotas de referência. A partir de 2027, `aliquotasNominais` passa a ser obrigatório em cada item, e o resultado sai como `CALCULO_SIMULADO` com o header `x-warning-dados-simulados`. Avise o usuário.
- **Não é consultoria tributária.** Diga isso ao entregar um produto ou um cálculo que vai ser usado de verdade, e recomende validar com um contador.
- **Dados de clientes:** a API online é do governo; não mande dados pessoais (CPF, nome) para ela, que nem pede isso. Em produção, rode local.
- **Atualize com a Receita.** A base de dados e as regras mudam com frequência (veja o `CHANGELOG.md` do repositório oficial). Deixe a versão fixada e um jeito simples de atualizar.
