# Preservação Cariniana
## Descrição

O plugin Preservação Cariniana tem o intuito de facilitar o processo de preservação digital de periódicos OJS por meio da Rede Cariniana. Sua principal funcionalidade é o envio de um e-mail para um endereço previamente definido com os seguintes dados de identificação do periódico:

* Editora/Instituição
* Título do periódico
* ISSN
* eISSN
* URL base
* Identificador do periódico
* Anos disponíveis
* Notas e Comentários

## Instalação

1. Entre na área administrativa do seu OJS através do __Painel de Controle__.
2. Navegue para `Configurações do Website`> `Plugins`> `Enviar novo plugin`.
3. Selecione o arquivo __carinianaPreservation.tar.gz__.
4. Clique em __Salvar__ e o plugin estará instalado no seu websites.

## Configuração

Após a instalação do plugin, é necessário fazer a sua configuração. Na aba `Plugins instalados` em `Configurações do Website`, procure o plugin "Preservação Cariniana" e abra as suas configurações. Na janela que abrir, você deve informar um e-mail de destino, para o qual será enviado o e-mail com os dados necessários para preservação do periódico.

Após essa configuração, o plugin está pronto para uso.

## Funcionalidades

Na aba `Plugins instalados` em `Configurações do Website`, procure o plugin "Preservação Cariniana". Ele possuirá uma opção chamada "Submissão para preservação". Ao clicar nessa opção será aberta uma janela, para confirmação do envio do e-mail com os dados. Ao clicar em "Submeter", será enviado um e-mail para o endereço configurado anteriormente, contendo uma planilha em anexo, com os dados de identificação do conteúdo do periódico.

Caso algum dos dados necessários para a submissão não tenha sido preenchido no sistema anteriormente, uma mensagem de erro será disparada. Para que não haja erros, os seguintes dados devem ser preenchidos:

* Editora
* ISSN eletrônico e impresso
* Ao menos uma edição publicada
* Resumo do periódico

## Licença
__Esse plugin é licenciado através da Licença Pública Geral GNU v3.0__

__Copyright (c) 2023 Lepidus Tecnologia__
