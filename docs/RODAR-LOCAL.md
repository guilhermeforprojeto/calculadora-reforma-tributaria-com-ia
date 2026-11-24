# Rodar a calculadora na sua máquina ou servidor

Resumo do README oficial de [ReceitaFederal/calculadora-rtc-regime-regular](https://github.com/ReceitaFederal/calculadora-rtc-regime-regular) (versão 1.7.2). Em caso de diferença, vale o oficial.

## Precisa de

- **Java 25** (ex.: [Eclipse Temurin 25](https://adoptium.net/)). Confira com `java -version`.
- Git e internet na primeira execução (o Maven Wrapper baixa o Maven e as dependências).
- Não precisa de banco externo: a calculadora usa um SQLite somente leitura que você gera no passo 2.

## 1. Clonar

```bash
git clone https://github.com/ReceitaFederal/calculadora-rtc-regime-regular.git
cd calculadora-rtc-regime-regular
```

Para produção, fixe uma versão: `git checkout <tag>` (veja as tags e o `CHANGELOG.md`).

## 2. Gerar o banco (obrigatório)

Sem ele, o perfil offline não sobe.

```bash
# Linux/macOS
mkdir -p ./calculadora/db
./mvnw flyway:migrate -Dflyway.configFiles=./flyway/flyway-pro.conf --batch-mode --errors
```

```bat
:: Windows
mkdir calculadora\db
mvnw.cmd flyway:migrate -Dflyway.configFiles=flyway\flyway-pro.conf --batch-mode --errors
```

Confirme que `./calculadora/db/calculadora-pro.db` existe.

## 3. Subir

```bash
./mvnw spring-boot:run -Dspring-boot.run.profiles=offline      # Linux/macOS
mvnw.cmd spring-boot:run "-Dspring-boot.run.profiles=offline"  # Windows
```

Ou gere o JAR e rode a partir da raiz do projeto (para achar `./calculadora/db`):

```bash
./mvnw clean package
java -Dspring.profiles.active=offline -jar target/api-regime-geral.jar
```

Pronto: `http://localhost:8080/api`, Swagger em `http://localhost:8080/api/swagger-ui.html`.

## 4. Conferir

```bash
curl http://localhost:8080/api/calculadora/dados-abertos/versao
curl http://localhost:8080/api/versao/status   # compara com a versão online
```

E rode o exemplo deste repositório contra a instância local:

```bash
BASE=http://localhost:8080/api/calculadora bash exemplos/curl.sh
```

## Testes oficiais

```bash
./mvnw clean test jacoco:report   # precisa do banco do passo 2
```

## Atualizar

1. `git fetch --tags` e leia o `CHANGELOG.md` (mudanças de arredondamento e de tabelas acontecem).
2. `git checkout <nova tag>`.
3. Gere o banco de novo (passo 2) e reinicie.
4. Rode seus casos de teste e compare com os resultados guardados.

## Em servidor

- Rode como serviço (systemd no Linux, NSSM ou serviço do Windows) com usuário próprio, sem root.
- Deixe a porta 8080 só na rede interna; o seu sistema chama a calculadora, a internet não.
- Hospedagem compartilhada (cPanel) normalmente não roda Java: use uma VPS, um container ou a máquina da empresa, e chame da sua aplicação.

## Simples Nacional e DANFSe

- **Simples Nacional:** mesmo processo em [calculadora-rtc-simples-nacional](https://github.com/ReceitaFederal/calculadora-rtc-simples-nacional), porta 8082; o banco é gerado com a pasta `flyway/` do repositório do regime regular.
- **DANFSe (Go):** `go run ./cmd/danfse` ou `docker build -t danfse-go . && docker run -p 8080:8080 danfse-go` em [calculadora-rtc-gerador-danfse](https://github.com/ReceitaFederal/calculadora-rtc-gerador-danfse).
