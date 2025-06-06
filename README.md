# Preservação Cariniana

O plugin Preservação Cariniana tem o intuito de facilitar o processo de preservação digital de periódicos OJS por meio da Rede Cariniana. Sua principal funcionalidade é o envio de um e-mail para rede Carianiana com as seguintes informações do periódico a ser preservado:

* Editora/Instituição
* Título do periódico
* ISSN
* eISSN
* URL base
* Identificador do periódico
* Anos disponíveis
* Volumes das edições
* Notas e Comentários
* Versão do OJS

Além destas informações, também é enviado o Termo de Responsabilidade e Autorização para preservação na rede Cariniana, preenchido pelos responsáveis pelo periódico.

<div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/1089677111?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" frameborder="0" allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media" style="position:absolute;top:0;left:0;width:100%;height:100%;" title="Plugin de Preservação na Rede Cariniana"></iframe></div><script src="https://player.vimeo.com/api/player.js"></script>

## Compatibilidade

Este plugin é compatível com o **OJS** versão **3.3.0**.

## Instalação e Configuração

1. Acesse *Configurações -> Website -> Plugins -> Galeria de plugins*. Clique em **Preservação Cariniana** e, em seguida, clique em *Instalar*.

2. Na aba `Plugins instalados` em `Configurações do Website`, procure o plugin "Preservação Cariniana" e abra as suas configurações. Na nova janela, você deve anexar o Termo de Responsabilidade e Autorização preenchido e assinado pela pessoa responsável pelo periódico.

Opcionalmente você poderá informar um e-mail para o qual serão copiadas as informações enviadas para a rede Cariniana, quando o periódico for submetido para preservação.

Após a configuração, o plugin está pronto para uso.

## Funcionalidades

### Submissão para preservação

Na aba `Plugins instalados` em `Configurações do Website`, procure o plugin "Preservação Cariniana".

O plugin possui uma opção chamada "Submissão para preservação". Ao clicar nessa opção será aberta uma janela, para confirmação do envio do e-mail com os dados.

Ao clicar em "Submeter", será enviado um e-mail para a rede Cariniana, contendo como anexos o Termo de Responsabilidade e Autorização preenchido, uma planilha com os dados de identificação do conteúdo do periódico, e um documento XML contendo os dados para inserção do periódico na rede Cariania.

Caso algum dos dados necessários para a submissão não tenha sido preenchido no OJS anteriormente, uma mensagem de erro será mostrada.

Para que não haja erros, os seguintes dados devem ser preenchidos:

* Editora
* Título
* ISSN eletrônico ou impresso
* Ao menos uma edição publicada
* Abreviatura do periódico
* Resumo do periódico
* Contato Principal e Técnico do periódico

### Atualização de dados em preservação

Para periódicos que já foram submetidos para preservação na Rede Cariniana, utilizando o plugin, também há atualização dos dados.

* Na janela de submissão, será exibida uma mensagem informando a data e hora da última submissão ou aualização para preservação.
* Ao submeter manualmente o formulario, será enviado um e-mail para a rede Cariniana com o XML atualizado com os dados mais recentes do periódico.
* Sempre respeitando os dados requeridos para preservação, para determinar o sucesso do envio.

### Detecção automática de atualizações

Quando o plugin estiver **ativo** no periódico, ele irá monitorar diariamente de forma automatica, por diferencas nos dados preservados do periodico.
Caso hajam diferencas, o plugin irá enviar um e-mail para a rede Cariniana com os dados atualizados.

## Configuração de monitoramento automático

O monitoramento automático de atualizações utiliza o agendamento de tarefas Cron do OJS, através do plugin Acron que é instalado por padrão no OJS 3.3.x

Para executar via Cron, diretamente no servidor, é possível utilizar o comando:

```bash
php tools/runScheduledTasks.php ojs/plugins/generic/carinianaPreservation/scheduledTasks.xml
```

## Usando em ambiente de desenvolvimento ou para testes

* **Instalação de desenvolvimento**

Faça o clone do repositório e execute o comando `composer install' no diretório do plugin.

* **Envio de e-mail de testes**

Por padrão, o plugin envia e-mail para o IBICT. Para alterar o e-mail do destinatário em ambiente de testes, é necessário uma configuração adicional no OJS. Adicione as seguintes linhas no arquivo `config.inc.php`:

```
[carinianapreservation]

email_for_tests = "seu e-mail de testes"
```

## Licença

![License](https://img.shields.io/github/license/lepidus/carinianaPreservation)

**Esse plugin é licenciado através da Licença Pública Geral GNU v3.0**

**Copyright (c) 2023-2025 Lepidus Tecnologia**
