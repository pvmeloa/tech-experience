# Atividade Avaliativa - Fundamentos de PHP e Integração Web

Aluno: Pedro Vitor de Melo Alves
Turma: Desenvolvimento Web - 3005
Entrega: 23/11/2026

Parte 1 - Questões teóricas

(A parte 2 está nos arquivos do repositório: index.html, style.css, script.js e processar.php)


## Questão 1

a) Quando eu acesso http://localhost:8000/index.php o navegador faz uma requisição HTTP pro servidor pedindo esse arquivo. O servidor vê que é um arquivo .php e em vez de mandar ele direto, passa pro interpretador do PHP, que executa o código que está entre <?php e ?> ali mesmo no servidor. O que volta pro navegador é só o resultado dessa execução, ou seja, o HTML que foi gerado (o que saiu nos echo e as partes de HTML que já estavam no arquivo). Por isso no "exibir código-fonte" não aparece nenhuma tag PHP, o código original nunca sai do servidor.

Quando abro o arquivo com duplo clique (file://) não tem servidor nenhum no meio, o navegador só lê o arquivo do disco. Como o navegador não tem interpretador de PHP (ele só entende HTML, CSS e JavaScript), ele mostra o código como texto ou pede pra baixar.

b) O interpretador PHP é quem executa o script do lado do servidor. Ele recebe o arquivo junto com os dados da requisição ($_GET, $_POST, $_SERVER), roda o código e gera uma saída em texto. Essa saída é enviada como resposta HTTP, com o status (ex: 200) e o Content-Type, que normalmente é text/html. Então o que chega no navegador é texto puro, quase sempre HTML, e o navegador só renderiza.


## Questão 2

a) No GET os dados vão na própria URL, na query string, tipo processar.php?nome=Ana&turno=noite. Eles ficam visíveis na barra de endereço, no histórico e nos logs do servidor, e tem limite de tamanho por causa da URL. É indicado pra coisas de consulta que não alteram nada, como busca, filtro e paginação, porque dá pra repetir a requisição e salvar o link.

No POST os dados vão no corpo (body) da requisição, não aparecem na URL. É o indicado quando o envio cria ou altera alguma coisa no servidor, como cadastro, login e formulário de inscrição, e também quando tem mais dados ou dados sensíveis. No PHP eles chegam em $_GET e $_POST.

b) Essa afirmação está errada. O POST só muda o lugar onde o dado vai (sai da URL e vai pro corpo), mas ele não criptografa nada. Se a conexão for http://, o corpo da requisição passa pela rede em texto normal e qualquer um que intercepte o tráfego consegue ler, por exemplo numa rede Wi-Fi pública. E o próprio usuário consegue ver tudo que foi enviado na aba Network do DevTools.

O que realmente protege os dados é o HTTPS, que usa TLS (antigo SSL) pra criptografar a comunicação toda. O certo é usar POST quando faz sentido e HTTPS pra garantir o sigilo.


## Questão 3

a) Normalização é deixar o dado num formato padrão antes de validar, por exemplo tirar espaços sobrando no começo e no fim. Assim " ana@email.com " e "ana@email.com" viram a mesma coisa.

Validação é conferir se o dado obedece às regras: se é obrigatório e veio preenchido, se tem o tamanho certo, se o formato é válido, se o valor está entre as opções permitidas. Se não passar, o dado é recusado e a gente guarda uma mensagem de erro.

Escape de saída é tratar o dado na hora de mostrar no HTML, convertendo os caracteres especiais pra que apareçam como texto e não sejam interpretados como código.

Uma etapa não substitui a outra. Um nome com mais de 3 letras é válido mesmo tendo <script> dentro, então mesmo depois de validar ainda precisa do escape na saída.

b) Exemplos de funções que usei:

- Normalização: trim() e o operador ??, por exemplo `$email = trim($_POST['email'] ?? '');`
- Validação: filter_var($email, FILTER_VALIDATE_EMAIL) pro e-mail, mb_strlen($nome) pra contar o tamanho do nome (conta certo com acento), in_array($valor, $permitidos, true) pra conferir se está na lista permitida e is_array() pra ver se veio uma lista.
- Escape: `htmlspecialchars($nome, ENT_QUOTES, 'UTF-8')`

O htmlspecialchars troca < por &lt;, > por &gt;, & por &amp;, aspas duplas por &quot; e, com ENT_QUOTES, aspas simples também. Isso evita o XSS (Cross-Site Scripting). Sem ele, se alguém digitar <script>alert("XSS")</script> no nome, o navegador executaria esse script na página de resposta, e um atacante poderia roubar cookies e sessão ou mudar o conteúdo da página. Com o escape o texto só aparece escrito na tela, sem executar.


## Questão 4

a) É uma falha grave porque tudo que está no navegador está nas mãos do usuário. O required, o type="email", o minlength e o JavaScript rodam no computador do cliente e dá pra burlar fácil. O servidor só recebe uma requisição HTTP e não tem como saber se ela veio mesmo do formulário. Se o processar.php grava o $_POST sem validar, ele aceita qualquer coisa: campo vazio, e-mail inválido, opção que não existe, texto enorme ou até código malicioso. Isso compromete a integridade dos dados e a segurança do sistema.

b) Alguns casos em que a validação do cliente não serve de nada:

- O usuário desativa o JavaScript no navegador, aí o script de validação nem roda.
- O usuário abre o DevTools (inspecionar elemento) e apaga o required ou o minlength, ou muda o value de uma opção do select pra algo que não existe.
- O envio é feito direto pro processar.php sem usar o formulário, pelo Postman, Insomnia ou cURL, por exemplo: `curl -X POST -d "nome=a&email=x" http://localhost:8000/processar.php`
- Bots que mandam requisições automáticas.

A validação no cliente serve pra melhorar a experiência do usuário: ele vê o erro na hora, sem precisar enviar e esperar a resposta. Mas quem garante de verdade que os dados estão certos é a validação no servidor, porque essa não tem como ser burlada. Por isso ela é obrigatória e tem que repetir todas as regras do cliente.


## Questão 5

a) O ?? é o operador de coalescência nula. Ele usa o valor da esquerda se ele existir e não for null, senão usa o valor da direita:

```php
$nome = $_POST['nome'] ?? '';
```

Sem ele, se a chave não foi enviada, o PHP dá o aviso "Warning: Undefined array key". Isso acontece bastante com checkbox e radio, porque quando nenhum é marcado o navegador nem manda o campo, e também quando alguém envia a requisição faltando campos de propósito. Com o ?? a variável recebe um valor padrão e depois a validação cuida do caso vazio. Fica mais curto que usar isset com ternário.

b) No HTML todos os checkboxes recebem o mesmo name com colchetes no final:

```html
<input type="checkbox" name="oficinas[]" value="html_css">
<input type="checkbox" name="oficinas[]" value="php_backend">
```

O navegador manda um valor pra cada item marcado e, por causa do [], o PHP junta tudo num array: $_POST['oficinas'] fica ['html_css', 'php_backend']. Sem os colchetes só ficaria o último valor marcado.

No PHP não dá pra confiar que vai chegar um array, porque alguém pode mandar oficinas=texto direto. Então primeiro confiro com is_array(), depois passo por cada item conferindo se está na lista permitida (whitelist) com in_array usando true no terceiro parâmetro, que faz a comparação estrita (compara valor e tipo e evita conversão automática). Por último vejo se a lista não está vazia:

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
