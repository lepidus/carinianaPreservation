# Preservação Cariniana
## Descrição

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

## Compatibilidade

Este plugin é compatível com o OJS versão 3.3.0-x.

## Instalação

1. Entre na área administrativa do seu OJS através do __Painel de Controle__.
2. Navegue para `Configurações do Website`> `Plugins`> `Enviar novo plugin`.
3. Selecione o arquivo __carinianaPreservation.tar.gz__.
4. Clique em __Salvar__ e o plugin estará instalado no seu websites.

## Instalação de desenvolvimento

Faça o clone do repositório e execute o comando `composer install' no diretório do plugin.

## Configuração

Após a instalação do plugin, é necessário fazer a sua configuração. Na aba `Plugins instalados` em `Configurações do Website`, procure o plugin "Preservação Cariniana" e abra as suas configurações. Na janela que abrir, você deve anexar o Termo de Responsabilidade e Autorização preenchido e assinado pela pessoa responsável pelo periódico.

Opcionalmente você poderá informar um e-mail para o qual serão copiadas as informações enviadas para a rede Cariniana, quando o periódico for submetido para preservação.

Após a configuração, o plugin está pronto para uso.

## Funcionalidades

Na aba `Plugins instalados` em `Configurações do Website`, procure o plugin "Preservação Cariniana". Ele possuirá uma opção chamada "Submissão para preservação". Ao clicar nessa opção será aberta uma janela, para confirmação do envio do e-mail com os dados.

Ao clicar em "Submeter", será enviado um e-mail para a rede Cariniana, contendo como anexos o Termo de Responsabilidade e Autorização preenchido, uma planilha com os dados de identificação do conteúdo do periódico, e um documento XML contendo os dados para inserção do periódico na rede Cariania.

Caso algum dos dados necessários para a submissão não tenha sido preenchido no OJS anteriormente, uma mensagem de erro será disparada. Para que não haja erros, os seguintes dados devem ser preenchidos:

* Editora
* Título
* ISSN eletrônico ou impresso
* Ao menos uma edição publicada
* Abreviatura do periódico
* Resumo do periódico
* Contato Principal e Técnico do periódico

## Licença
__Esse plugin é licenciado através da Licença Pública Geral GNU v3.0__

__Copyright (c) 2023-2025 Lepidus Tecnologia__
