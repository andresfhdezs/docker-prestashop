#!/usr/bin/env bash

# Descarga PrestaShop
wget https://download.prestashop.com/download/releases/prestashop_1.6.1.23.zip

# Descomprime PrestaShop
unzip prestashop_1.6.1.23.zip

# Elimina el .zip y .htlm
rm prestashop_1.6.1.23.zip Install_PrestaShop.html

# Copia la carpeta de PrestaShop a la carpeta publica
cp -r prestashop/ public/

rm -r prestashop

#Set the correct user and group ownership for the PrestaShop directory
sudo chown -R www-data:www-data public/prestashop/