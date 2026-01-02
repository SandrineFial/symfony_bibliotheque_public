FROM php:8.4-apache

# SÉCURITÉ: L'image PHP utilise root par défaut, mais Apache change automatiquement 
# vers www-data lors de l'exécution. Le script d'entrée applique les permissions appropriées.

# Créer un utilisateur non-root pour améliorer la sécurité
RUN groupadd -r appuser && useradd -r -g appuser appuser

# Installer les extensions et dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    intl \
    opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configuration PHP pour production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Forcer l'utilisation d'IPv4 pour résoudre les problèmes de connectivité Supabase
RUN echo 'precedence ::ffff:0:0/96  100' >> /etc/gai.conf

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier composer.json et composer.lock en premier (cache Docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copier explicitement seulement les fichiers nécessaires pour la production
COPY .env.prod ./
COPY src/ ./src/
COPY config/ ./config/
COPY templates/ ./templates/
COPY public/ ./public/
COPY migrations/ ./migrations/
COPY bin/ ./bin/

# Finaliser l'installation Composer
RUN composer dump-autoload --optimize --classmap-authoritative

# Créer un fichier manifest.json vide pour éviter l'erreur
RUN mkdir -p public/build && echo '{}' > public/build/manifest.json

# Renommer .env.prod en .env pour la production
RUN mv .env.prod .env

# Note: Le cache sera généré au démarrage du conteneur
# car il a besoin des variables d'environnement de production (DATABASE_URL, etc.)

# Configuration Apache pour port non-privilégié (satisfait SonarQube)
RUN a2enmod rewrite
COPY apache.conf /etc/apache2/sites-available/000-default.conf
# Configurer Apache pour écouter sur le port 8080 (non-privilégié)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf

# Créer les dossiers nécessaires et définir les permissions
RUN mkdir -p var/cache var/log /tmp/sessions \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data var \
    && chown -R www-data:www-data /tmp/sessions \
    && chmod -R 775 var \
    && chmod -R 775 /tmp/sessions

# Copier le script d'entrée
COPY bin/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# SÉCURITÉ: Définir explicitement l'utilisateur non-root pour satisfaire SonarQube
# Apache peut maintenant démarrer en www-data car il écoute sur un port non-privilégié
USER www-data

# Exposer le port non-privilégié
EXPOSE 8080

# Commande de démarrage avec cache warmup et migrations
CMD ["/usr/local/bin/docker-entrypoint.sh"]
