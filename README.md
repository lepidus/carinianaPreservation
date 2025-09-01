# Preservação Cariniana

[![Latest Release](https://img.shields.io/github/v/release/lepidus/carinianaPreservation)](https://github.com/lepidus/carinianaPreservation/releases)

[Português (BR)](./README.md) | [English](./README.en.md) | [Español](./README.es.md)

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

Além destas informações, no primeiro envio também é enviado o *Termo de Responsabilidade e Autorização para preservação na rede Cariniana*, preenchido pelos responsáveis pelo periódico.

## Vídeo de Apresentação

[![Assista ao vídeo de apresentação no Vimeo](https://img.shields.io/badge/Assista%20ao%20vídeo%20de%20apresentação%20-Clique%20aqui-blue?logo=vimeo)](https://vimeo.com/997938301/c62617794b)

## Compatibilidade

Este plugin é compatível com o **OJS** versão **3.3.0**.

## Instalação e Configuração

1. Acesse *Configurações -> Website -> Plugins -> Galeria de plugins*. Clique em **Preservação Cariniana** e, em seguida, clique em *Instalar*.
2. Acesse *Distribuição -> Arquivamento*. Habilite a opção para o LOCKSS poder armazenar e distribuir o conteúdo do periódico. Salve.
3. Na aba `Plugins instalados` em `Configurações do Website`, procure o plugin "Preservação Cariniana" e abra as suas configurações. Na nova janela, você deve anexar o Termo de Responsabilidade e Autorização preenchido e assinado pela pessoa responsável pelo periódico.

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

```ini
[carinianapreservation]
email_for_tests = "seu e-mail de testes"
```

## Licença

![License](https://img.shields.io/github/license/lepidus/carinianaPreservation)

**Esse plugin é licenciado através da Licença Pública Geral GNU v3.0**

**Copyright (c) 2023-2025 Lepidus Tecnologia**
