#!/bin/bash
set -e

# Debug des variables d'environnement
echo "=== DEBUG ENV ==="
echo "APP_ENV: $APP_ENV"
echo "DATABASE_URL: ${DATABASE_URL:0:30}..." # Affiche juste le début pour sécurité
echo "SKIP_MIGRATIONS: $SKIP_MIGRATIONS"
echo "=================="

# Attendre que la base de données soit prête (si nécessaire)
if [ -n "$DATABASE_URL" ]; then
    echo "Waiting for database to be ready..."
    sleep 5
    
    # Test de connectivité à la base de données
    echo "🔍 Test de connectivité à la base de données..."
    if ! timeout 10 php bin/console doctrine:schema:validate --skip-mapping 2>/dev/null; then
        echo "⚠️  Impossible de se connecter à la base de données"
        if [ "$SKIP_MIGRATIONS" != "true" ]; then
            echo "💡 Pour ignorer les migrations, définir SKIP_MIGRATIONS=true"
            echo "🚀 Continuer quand même le démarrage (base existante probable)"
            # Ne plus arrêter le conteneur, juste noter l'information
        fi
    fi
fi

# Générer le cache de production
echo "🔄 Clearing and warming up cache..."
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

# Exécuter les migrations seulement si explicitement demandé
if [ "$SKIP_MIGRATIONS" = "true" ]; then
    echo "⏭️  Migrations ignorées (SKIP_MIGRATIONS=true)"
elif [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🔄 Exécution des migrations (RUN_MIGRATIONS=true)..."
    if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null; then
        echo "✅ Migrations terminées avec succès"
    else
        echo "⚠️  Erreur lors des migrations - continuer sans migration"
        echo "💡 La base de données existe peut-être déjà"
    fi
else
    echo "⏭️  Migrations ignorées par défaut (définir RUN_MIGRATIONS=true pour les activer)"
fi

# Note: Les permissions sont déjà définies dans le Dockerfile
# car nous fonctionnons maintenant en tant que www-data

echo "🚀 Démarrage d'Apache..."
# Démarrer Apache
exec apache2-foreground
