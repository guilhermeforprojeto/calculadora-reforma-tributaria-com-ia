# Ideias de produto em cima da calculadora

A Receita publicou o motor de cálculo com licença MIT: dá para usar em produto comercial, desde que o aviso de copyright e a licença dela acompanhem o código copiado. O motor é igual para todo mundo; o valor do produto está **em volta** dele: classificar os itens certo, encaixar no fluxo do cliente e explicar o resultado.

## Ideias

| Produto | Para quem | O que você constrói além da calculadora |
| --- | --- | --- |
| Simulador da reforma para o meu negócio | Pequenas e médias empresas | Importar a planilha de produtos ou o XML das notas do último mês, sugerir cClassTrib por item, mostrar quanto muda a carga com e sem reduções, relatório em PDF |
| Painel do contador | Escritórios de contabilidade | Vários clientes, conferência em lote das notas emitidas, alerta de item com `INCONSISTENCIA_ENTRADA`, histórico por versão da calculadora |
| Plugin de loja virtual | Lojas em WooCommerce, Shopify, Nuvemshop, VTEX | Calcular CBS/IBS no carrinho e no pedido, guardar a memória de cálculo junto do pedido |
| Conferência de XML antes de emitir | Emissores de NF-e/NFS-e, ERPs pequenos | Gerar e validar os grupos de tributação com `/xml/generate` e `/xml/validate`, mostrar o erro na linha certa |
| API gerenciada | Desenvolvedores que não querem rodar Java | Hospedar a calculadora atualizada, com SLA, chave de API, cache e histórico de versões |
| Classificador de produtos | Quem tem catálogo grande | Sugerir NCM/NBS → cClassTrib usando os dados abertos (e IA para descrição → NCM), sempre com aprovação humana |

## Antes de vender

- **Valide a demanda** antes de programar (ex.: [valide-antes-de-programar](https://github.com/guilhermeforprojeto/valide-antes-de-programar)). Já existem pacotes e APIs em volta dessa calculadora; descubra o que falta para o seu público.
- **Responsabilidade:** deixe claro nos termos que o produto usa a calculadora oficial e não substitui o contador. Erro de classificação vira autuação para o cliente.
- **Versão:** mostre ao cliente qual versão da calculadora e da base calculou cada valor, e avise quando houver atualização.
- **Datas:** em 2026 é ano de teste (CBS 0,9% e IBS 0,1% de referência). A partir de 2027, as alíquotas passam a ser informadas por item (`aliquotasNominais`). Planeje a tela para isso.
- **Dados do cliente:** rode a calculadora na sua infraestrutura; não mande dados de clientes para a API piloto.
- **Licença:** ao copiar ou modificar código da Receita, mantenha o arquivo `LICENSE` (MIT) dela junto. Usar a calculadora como serviço separado, sem copiar código, é o caminho mais simples.
- **Segurança:** antes de abrir para clientes, revise com [revisao-de-seguranca-com-ia](https://github.com/guilhermeforprojeto/revisao-de-seguranca-com-ia).
