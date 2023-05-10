# IOPAY for WooCommerce

Receba pagamentos por cartão de crédito, pix e boleto bancário.

O [IOPAY](https://iopay.com.br/) é a melhor forma de receber pagamentos online por cartão de crédito, pix e boleto bancário, sendo possível o cliente fazer todo o pagamento sem sair da sua loja WooCommerce.

Saiba mais como o Iopay funciona em: https://iopay.com.br/

## Compatibilidade

Compatível com desde a versão 7.4.x do WooCommerce.

Este plugin funciona integrado com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/), desta forma é possível enviar documentos do cliente como "CPF" ou "CNPJ", além dos campos "número" e "bairro" do endereço. 

## Instalação

Confira o nosso [guia de instalação](https://docs.iopay.com.br/products/modulos-para-ecommerce/wordpress-woocommerce).

## Dúvidas frequentes

### Instalação do plugin

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.
* Ative o plugin.

### Requerimentos

* WooCommerce versão 7.4.x ou posterior instalado e ativo;
* Brazilian Market on WooCommerce versão 3.4.x ou posterior instalado e ativo;
* Uma conta ativa no [IOPAY](https://iopay.com.br/);
* [Credenciais de integração](https://docs.iopay.com.br/credenciais-de-acesso) para IOPAY.

### Configurações do Plugin

Com o plugin instalado acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Pagamentos" e configure as opção "IOPAY - Boleto bancário", "IOPAY - Cartão de crédito" e "IOPAY - PIX".

Habilite a opção que você deseja, preencha as opções de **Email Auth**, **Iopay API Key** and **Iopay Encryption Key** que você pode encontrar dentro da sua conta na IOPAY em **https://minhaconta.iopay.com.br/settings/online_payment**.

Também será necessário utilizar o plugin [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) para poder enviar campos de CPF e CNPJ.

Pronto, sua loja já pode receber pagamentos pelo IOPAY.

Mais informações sobre as configurações do plugin em: [guia de instalação](https://docs.iopay.com.br/products/modulos-para-ecommerce/wordpress-woocommerce).

### Qual é a licença do plugin?

Este plugin está licenciado como GPL.

### O que eu preciso para utilizar este plugin?

* Ter instalado o plugin WooCommerce 7.4.x ou superior;
* Possuir uma conta na IOPAY;
* Pegar suas credencias na IOPAY.

### Quanto custa a IOPAY?

Confira os preços em "https://iopay.com.br/precos".

### O pedido foi pago e ficou com o status de "processando" e não como "concluído", isto está certo ?

Sim, está certo e significa que o plugin esta trabalhando como deveria.

Todo gateway de pagamentos no WooCommerce deve mudar o status do pedido para "processando" no momento que é confirmado o pagamento e nunca deve ser alterado sozinho para "concluído", pois o pedido deve ir apenas para o status "concluído" após ele ter sido entregue.

Para produtos baixáveis a configuração padrão do WooCommerce é permitir o acesso apenas quando o pedido tem o status "concluído", entretanto nas configurações do WooCommerce na aba *Produtos* é possível ativar a opção **"Conceder acesso para download do produto após o pagamento"** e assim liberar o download quando o status do pedido esta como "processando".

### É obrigatório enviar todos os campos para processar o pagamento?

Não é obrigatório caso você não utilize antifraude.

É possível remover os campos de endereço, empresa e telefone, mantendo apenas nome, sobrenome e e-mail utilizando o plugin [WooCommerce Digital Goods Checkout](https://wordpress.org/plugins/wc-digital-goods-checkout/), mas lembre-se que esses campos só são obrigatórios quando seu plano tiver antifraude incluído.
