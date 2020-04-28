# Lancement des containers
docker-compose up --build

# Import de 100 000 titres de films / séries issus de la plateforme IMDB et installation des dépendances JS
docker exec -it api_redisearch sh -c "cd /var/www/html && ./scripts/init.sh"

# Accès au moteur de recherche
http://127.0.0.1:8100/

Note: Symfony et Redisearch sont lents sous OSX
