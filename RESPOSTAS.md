# Atividade Avaliativa — Fundamentos de PHP e Integração Web

| | |
|---|---|
| **Estudante** | Pedro Vitor de Melo Alves |
| **Turma / Disciplina** | Desenvolvimento Web — 3005 |
| **Data de entrega** | 23/11/2026 |

**Parte 1 — Respostas das questões teóricas**

A Parte 2 (projeto prático) está nos arquivos deste repositório: `index.html`, `style.css`, `script.js` e `processar.php`.

---

## Questão 1 — Ciclo de requisição-resposta e execução no servidor

**a) Por que o código PHP nunca chega ao navegador**

O fluxo acontece em três etapas:

1. **Requisição:** ao acessar `http://localhost:8000/index.php`, o navegador envia uma requisição HTTP (`GET /index.php`) ao servidor web.
2. **Processamento no servidor:** o servidor identifica pela extensão `.php` que o arquivo não é estático e o entrega ao interpretador PHP. O interpretador executa todo o código entre `<?php ... ?>` (variáveis, condições, laços, acesso a dados) no próprio servidor.
3. **Resposta:** apenas o **resultado** dessa execução — o texto produzido por `echo`, `print` e pelos trechos de HTML fora das tags PHP — é devolvido ao navegador no corpo da resposta HTTP.

O código-fonte original fica no servidor; o que trafega pela rede é somente a saída gerada. Por isso o "Exibir código-fonte" ou o DevTools mostram apenas HTML.

Já ao abrir o arquivo com duplo clique (`file://`), não há servidor HTTP nem interpretador envolvidos: o navegador apenas lê o arquivo do disco. Como o navegador **não possui interpretador PHP embutido** (ele só entende HTML, CSS e JavaScript), ele exibe o código como texto puro ou oferece o download.

**b) Papel do interpretador PHP e formato da resposta**

O interpretador PHP é o **motor de execução no lado do servidor**: recebe o script do servidor web, junto com os dados da requisição (superglobais `$_GET`, `$_POST`, `$_SERVER`), executa as instruções e gera uma saída de texto. O servidor web então empacota essa saída numa resposta HTTP com cabeçalhos (status, como `200 OK`, e `Content-Type: text/html; charset=UTF-8`) e a envia ao cliente.

O conteúdo efetivamente entregue é **texto puro — normalmente HTML** (podendo ser também JSON, XML, CSS etc., conforme o `Content-Type`), que o navegador apenas renderiza.

---

## Questão 2 — GET versus POST na prática

**a) Diferença técnica e uso recomendado**

| Aspecto | GET | POST |
|---|---|---|
| Onde vão os dados | Na URL, como *query string*: `processar.php?nome=Ana&turno=noite` | No **corpo (body)** da requisição HTTP |
| Visibilidade | Aparecem na barra de endereços, histórico, favoritos e logs do servidor | Não aparecem na URL (mas são visíveis no DevTools e na rede) |
| Tamanho | Limitado pelo comprimento máximo da URL | Adequado para volumes maiores e uploads de arquivos |
| Semântica | Leitura/consulta; **idempotente** (repetir não altera o estado) | Envio de dados que **criam ou alteram** recursos |
| Uso recomendado | Buscas, filtros, paginação, links compartilháveis | Cadastros, logins, formulários de inscrição, alterações, dados sensíveis |

No PHP, os dados chegam em `$_GET` ou `$_POST`, respectivamente.

**b) Por que POST não é sinônimo de segurança**

A afirmação é falsa. O POST apenas muda o **lugar** onde os dados são colocados (do endereço para o corpo da mensagem); ele **não criptografa nada**. Numa conexão `http://`, o corpo da requisição trafega em **texto claro** e pode ser lido por qualquer um que intercepte o tráfego (rede Wi-Fi pública, proxy, ataque *man-in-the-middle*). Além disso, o próprio usuário vê todo o payload na aba *Network* do DevTools.

A tecnologia indispensável para garantir o sigilo é o **HTTPS**, que é o HTTP sobre **TLS** (sucessor do SSL). O TLS criptografa toda a comunicação (URL, cabeçalhos e corpo) e autentica o servidor por certificado digital. A combinação correta é: **POST para a semântica certa + HTTPS para o sigilo**.

---

## Questão 3 — Normalização, validação e escape de saída

**a) Objetivo de cada etapa**

1. **Normalização:** preparar e padronizar o dado bruto antes de aplicar as regras — remover espaços extras, garantir o tipo esperado, uniformizar maiúsculas/minúsculas. Evita que `"  ana@ex.com "` seja considerado diferente de `"ana@ex.com"`.
2. **Validação:** verificar se o dado normalizado cumpre as regras de negócio e de tipo (obrigatoriedade, tamanho, formato, pertencimento a uma lista permitida). Se não cumprir, o dado é **rejeitado** e uma mensagem de erro é registrada.
3. **Escape de saída:** no momento de exibir o dado no HTML, converter os caracteres com significado especial em entidades, para que sejam mostrados como texto e não interpretados como código.

Uma etapa não substitui a outra: um nome pode ser válido (mais de 3 caracteres) e ainda conter `<script>`; por isso o escape é sempre obrigatório na saída.

**b) Funções nativas e vulnerabilidade evitada**

| Etapa | Funções PHP | Exemplo |
|---|---|---|
| Normalização | `trim()`, `mb_strtolower()`, operador `??`, `(int)` | `$email = trim($_POST['email'] ?? '');` |
| Validação | `filter_var()`, `mb_strlen()`, `in_array(..., true)`, `is_array()` | `filter_var($email, FILTER_VALIDATE_EMAIL)` / `mb_strlen($nome) >= 3` |
| Escape de saída | `htmlspecialchars()` | `echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');` |

O `htmlspecialchars` converte `<` → `&lt;`, `>` → `&gt;`, `&` → `&amp;`, `"` → `&quot;` e, com `ENT_QUOTES`, `'` → `&#039;`. Isso impede o **XSS (Cross-Site Scripting)**: sem o escape, se alguém digitar `<script>alert("XSS")</script>` no nome, o navegador executaria esse script na página de resposta — permitindo roubo de cookies/sessão, redirecionamentos ou alteração do conteúdo. Com o escape, o texto aparece literalmente na tela, inofensivo.

---

## Questão 4 — Validação no cliente versus no servidor

**a) Por que é uma falha crítica**

O navegador é um **ambiente não confiável**: está sob controle total do usuário. Atributos como `required`, `type="email"` e `minlength`, e scripts JavaScript, rodam na máquina do cliente e podem ser contornados com facilidade. O servidor, por sua vez, recebe apenas uma requisição HTTP — ele não tem como saber se ela veio do formulário original. Ao gravar `$_POST` sem validação, o `processar.php` aceita qualquer coisa: campos vazios, e-mails inválidos, valores fora das opções, textos gigantes ou código malicioso, comprometendo a **integridade dos dados** e a **segurança** da aplicação (XSS armazenado, injeções, dados corrompidos).

**b) Cenários em que a validação do cliente é ignorada e papel de cada uma**

- **JavaScript desativado** no navegador: o script de validação simplesmente não executa.
- **Edição no DevTools** (Inspecionar elemento): o usuário remove `required`/`minlength`, troca `type="email"` por `text` ou altera o `value` de uma opção do `<select>` para algo fora da lista.
- **Requisição direta**, sem usar o formulário: com cURL, Postman, Insomnia ou um script, é possível enviar qualquer POST para `processar.php`. Exemplo: `curl -X POST -d "nome=a&email=x" http://localhost:8000/processar.php`.
- **Bots e automações** que enviam dados em massa direto ao endpoint.

**Papéis:**

- **Validação no cliente = experiência do usuário (UX).** Dá feedback imediato, evita idas e voltas ao servidor e orienta o preenchimento. É uma conveniência, não uma barreira.
- **Validação no servidor = garantia.** É a única que não pode ser contornada; garante integridade, consistência e segurança. É **obrigatória**, e deve repetir (e ampliar) todas as regras do cliente.

---

## Questão 5 — Superglobais e entradas complexas

**a) Operador de coalescência nula (`??`)**

O operador `??` retorna o valor à esquerda se ele **existir e não for `null`**; caso contrário, retorna o valor padrão à direita:

```php
$nome = $_POST['nome'] ?? '';
```

Ele evita o aviso **`Warning: Undefined array key "nome"`**, que ocorre quando se tenta ler uma chave que não foi enviada. Isso é comum em checkboxes e botões de rádio não marcados (o navegador simplesmente não envia o campo) ou em requisições forjadas sem certos campos. O `??` fornece um *fallback* seguro, deixa o código mais limpo do que `isset($_POST['nome']) ? $_POST['nome'] : ''` e permite que a validação trate o valor vazio de forma controlada.

**b) Seleção múltipla com `name="oficinas[]"`**

**No HTML:** cada checkbox recebe o mesmo nome com colchetes no final:

```html
<input type="checkbox" name="oficinas[]" value="html_css">
<input type="checkbox" name="oficinas[]" value="php_backend">
```

O navegador envia um par para cada item marcado (`oficinas[]=html_css&oficinas[]=php_backend`). O sufixo `[]` instrui o PHP a agrupar esses valores num **array**: `$_POST['oficinas'] = ['html_css', 'php_backend']`. Sem os colchetes, só o último valor seria mantido.

**No PHP:** o backend não pode presumir o formato, pois a requisição pode ter sido forjada (ex.: `oficinas=texto` em vez de array). A validação correta:

```php
$permitidas = ['html_css', 'js_dom', 'php_backend'];
$oficinas = $_POST['oficinas'] ?? [];

if (!is_array($oficinas)) {
    $erros[] = 'Formato inválido para as oficinas.';
} else {
    foreach ($oficinas as $item) {
        if (!in_array($item, $permitidas, true)) {
            $erros[] = 'Oficina inválida.';
            break;
        }
    }
    if (count($oficinas) === 0) {
        $erros[] = 'Selecione pelo menos uma oficina.';
    }
}
```

1. `is_array()` confirma que o dado é realmente uma lista.
2. Cada item é comparado com a **whitelist** usando `in_array($item, $permitidos, true)`. O terceiro parâmetro `true` ativa a **comparação estrita** (valor e tipo), evitando coincidências indevidas por conversão automática de tipos.
3. Verifica-se que a lista não está vazia.
