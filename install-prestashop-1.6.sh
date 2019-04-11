#!/usr/bin/env bash

# Descarga PrestaShop
wget https://www.prestashop.com/download/old/prestashop_1.6.1.13.zip

# Descomprime PrestaShop
unzip prestashop_1.6.1.13.zip

# Elimina el .zip 
rm prestashop_1.6.1.13.zip Install_PrestaShop.html

# Copia la carpeta de PrestaShop a la carpeta publica
cp -r prestashop/ public/prestashop

rm -r prestashop

#Set the correct user and group ownership for the PrestaShop directory
sudo chown -R www-data:www-data public/prestashop/