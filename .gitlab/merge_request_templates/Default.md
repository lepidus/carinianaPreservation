## Validação pré-MR

Marque as validações executadas antes de pedir revisão:

- [ ] Branch alvo corresponde à versão OJS compatível (`stable-3_3_0`, `stable-3_4_0` ou `main`).
- [ ] `php -l` executado nos arquivos PHP alterados.
- [ ] PHP-CS-Fixer executado com regra equivalente à CI.
- [ ] PHPUnit do plugin executado dentro do checkout OJS da versão alvo.
- [ ] Plugin copiado fisicamente para `plugins/generic/carinianaPreservation` quando a validação usou Docker.
- [ ] Cypress/integração executado quando a mudança tocou template, formulário, upload, modal, JS, CSRF ou fluxo de navegador.

Validações não executadas e motivo:

-

Risco residual conhecido:

-
