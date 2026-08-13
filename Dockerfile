FROM node:18-alpine

WORKDIR /app

# Copier tous les fichiers
COPY . .

# Installer les dépendances (si package.json existe à la racine)
RUN if [ -f package.json ]; then npm install; fi

# Exposer le port
EXPOSE 3000

# Démarrer l'application
CMD ["sh", "-c", "if [ -f server.js ]; then node server.js; elif [ -f index.js ]; then node index.js; else echo 'No server file found'; fi"]
