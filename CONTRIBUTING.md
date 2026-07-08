# Contribuindo

Antes de abrir ou atualizar um merge request, rode a menor matriz de testes que cubra a superfície alterada. Não basta executar apenas testes de unidade quando a mudança atravessa browser, template, upload, formulário, sessão, CSRF, filesystem ou integração com o OJS.

## Validação mínima antes de MR

Para qualquer alteração de código PHP:

```bash
find . -name '*.php' -o -name '*.inc.php' | xargs -n1 php -l
php-cs-fixer fix --dry-run --rules=@PSR12 --using-cache=no --format=gitlab .
```

Rode também os testes de unidade do plugin dentro do checkout OJS da versão alvo. O plugin deve estar copiado fisicamente em `plugins/generic/carinianaPreservation`; não dependa de symlink quando a validação envolver Docker.

Para alterações em formulário, template, JavaScript, upload, modal, CSRF, handlers AJAX ou persistência acionada pela interface, rode também a suíte Cypress/integração correspondente antes de abrir ou atualizar o MR.

## Matriz por superfície alterada

| Superfície alterada | Validação obrigatória |
| --- | --- |
| Classes PHP, builders, factories, validações puras | `php -l`, PHP-CS-Fixer e PHPUnit |
| Persistência de settings, arquivos, e-mail, XML, CSV ou tarefas agendadas | `php -l`, PHP-CS-Fixer, PHPUnit e teste no OJS real da versão alvo |
| Template, formulário, modal, upload, JS, CSRF ou fluxo de navegador | `php -l`, PHP-CS-Fixer, PHPUnit e Cypress/integração |
| CI, empacotamento ou compatibilidade entre OJS 3.3, 3.4 e 3.5 | Validar o branch e a pipeline da versão alvo |

Se uma validação obrigatória não puder ser executada localmente, registre isso na descrição do MR antes de pedir revisão, com o motivo, o risco residual e qual pipeline deve cobrir a lacuna.

## Ramos de compatibilidade

O ramo alvo deve seguir a versão compatível do plugin:

* `stable-3_3_0`: série `v1.x.x`, compatível com OJS 3.3.
* `stable-3_4_0`: série `v2.x.x`, compatível com OJS 3.4.
* `main`: série `v3.x.x`, compatível com OJS 3.5.
