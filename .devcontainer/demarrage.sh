#!/usr/bin/env bash
# Demarrage de l'environnement, rejoue a chaque reveil du Codespace.

echo "→ Demarrage de la base de donnees..."
# Le nom du service depend de l'image du conteneur : « mariadb » sur Debian,
# « mysql » ailleurs. On essaie les deux plutot que de supposer.
if [ -f /etc/init.d/mariadb ]; then
    sudo service mariadb start
elif [ -f /etc/init.d/mysql ]; then
    sudo service mysql start
else
    echo "!! Aucun service de base de donnees trouve."
    echo "!! Lancer d'abord : bash .devcontainer/installation.sh"
fi

# Le serveur accepte les connexions quelques secondes apres le lancement du
# service. Sans cette attente, les premieres requetes echouent avec une
# erreur de connexion trompeuse.
echo "→ Attente de la base..."
for tentative in $(seq 1 30); do
    if sudo mysqladmin ping --silent 2>/dev/null; then
        echo "→ Base prete (apres ${tentative}s)."
        break
    fi
    if [ "$tentative" -eq 30 ]; then
        echo "!! La base n'a pas repondu en 30s."
    fi
    sleep 1
done

echo "→ Nettoyage des caches Laravel..."
php artisan optimize:clear || true

# Un serveur de la session precedente peut encore occuper le port 8000.
# Tuer « artisan serve » ne suffit pas : le processus enfant « php -S »
# survit et garde le port 8000, ce qui empeche le nouveau serveur de
# demarrer tout en continuant de repondre avec une sortie cassee.
pkill -f "artisan serve" 2>/dev/null || true
pkill -f "server.php" 2>/dev/null || true
sleep 1

echo "→ Lancement du serveur applicatif sur le port 8000..."
nohup php artisan serve --host=0.0.0.0 --port=8000 \
    > storage/logs/serve.log 2>&1 &

sleep 2
echo "→ Pret. Journal du serveur : storage/logs/serve.log"
