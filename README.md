=== IOPAY for WooCommerce ===
Contributors: Jeronimo Cardoso
Tags: woocommerce, iopay, payment
Requires at least: 4.0
Tested up to: 1.0.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Receba pagamentos por cartão de crédito, pix e boleto

== Description ==

O [IOPAY](https://iopay.com.br/) é a melhor forma de receber pagamentos online por cartão de crédito, pix e boleto bancário, sendo possível o cliente fazer todo o pagamento sem sair da sua loja WooCommerce.

Saiba mais como o Iopay funciona:

https://iopay.com.br/


= Compatibilidade =

Compatível com desde a versão 2.2.x do WooCommerce.

Este plugin funciona integrado com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/), desta forma é possível enviar documentos do cliente como "CPF" ou "CNPJ", além dos campos "número" e "bairro" do endereço. 

= Instalação =

Confira o nosso guia de instalação:

https://iopay.com.br/

= Dúvidas? =

Você pode esclarecer suas dúvidas usando:

faq

= Instalação do plugin: =

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.
* Ative o plugin.

= Requerimentos: =

É necessário possuir uma conta no [IOPAY](https://iopay.com.br/) e ter instalado o [WooCommerce](http://wordpress.org/plugins/woocommerce/).

= Configurações do Plugin: =

Com o plugin instalado acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Finalizar compra" e configure as opção "IOPAY - Boleto bancário", "IOPAY - Cartão de crédito" e "IOPAY - PIX".

Habilite a opção que você deseja, preencha as opções de Email, **IO_SELLER_ID** e **Secret - Chave de Criptografia** que você pode encontrar dentro da sua conta na IOPAY em **https://minhaconta.iopay.com.br/settings/online_payment**.

Também será necessário utilizar o plugin [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) para poder enviar campos de CPF e CNPJ.

Pronto, sua loja já pode receber pagamentos pelo IOPAY.

Mais informações sobre as configurações do plugin em: 

= Qual é a licença do plugin? =

Este plugin esta licenciado como GPL.

= O que eu preciso para utilizar este plugin? =

* Ter instalado o plugin WooCommerce 2.2 ou superior.
* Possuir uma conta na IOPAY.
* Pegar suas credencias na IOPAY.

= Quanto custa a IOPAY? =

Confira os preços em "https://iopay.com.br/precos".

= O pedido foi pago e ficou com o status de "processando" e não como "concluído", isto esta certo ? =

Sim, esta certo e significa que o plugin esta trabalhando como deveria.

Todo gateway de pagamentos no WooCommerce deve mudar o status do pedido para "processando" no momento que é confirmado o pagamento e nunca deve ser alterado sozinho para "concluído", pois o pedido deve ir apenas para o status "concluído" após ele ter sido entregue.

Para produtos baixáveis a configuração padrão do WooCommerce é permitir o acesso apenas quando o pedido tem o status "concluído", entretanto nas configurações do WooCommerce na aba *Produtos* é possível ativar a opção **"Conceder acesso para download do produto após o pagamento"** e assim liberar o download quando o status do pedido esta como "processando".

= É obrigatório enviar todos os campos para processar o pagamento? =

Não é obrigatório caso você não utilize antifraude.

É possível remover os campos de endereço, empresa e telefone, mantendo apenas nome, sobrenome e e-mail utilizando o plugin [WooCommerce Digital Goods Checkout](https://wordpress.org/plugins/wc-digital-goods-checkout/), mais lembre-se esses campos sào obrigatórios quando seu plano tiver antifraude incluido.

== Changelog ==

= 1.0.0 =

* Versão incial do plugin. 21/09/2022
