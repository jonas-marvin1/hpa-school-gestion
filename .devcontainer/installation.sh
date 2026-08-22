#!/usr/bin/env bash
# Installation initiale du Codespace. Ne s'execute qu'a la creation du conteneur.
# Volontairement sans « set -e » : l'echec d'un paquet ne doit pas interrompre
# tout le reste, ce qui etait le defaut de l'ancienne commande en une ligne.

echo "→ Mise a jour des depots..."
sudo apt-get update

echo "→ Extensions PHP..."
sudo apt-get install -y \
    php8.3-mysql php8.3-bcmath php8.3-xml php8.3-mbstring \
    php8.3-curl php8.3-zip php8.3-gd php8.3-intl

# Debian ne fournit pas de paquet « mysql-server » : c'est mariadb-server,
# compatible avec le driver mysql de Laravel et avec les dumps MySQL.
echo "→ Serveur de base de donnees..."
sudo apt-get install -y mariadb-server

echo "→ Dependances PHP..."
composer install

echo "→ Dependances JavaScript..."
npm install

echo "→ Installation terminee."
