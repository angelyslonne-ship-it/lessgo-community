FROM node:18-alpine

WORKDIR /app

# Copier les package.json d'abord (pour optimiser le cache)
COPY backend/package.json backend/
COPY frontend/package.json frontend/

# Installer les dépendances
RUN cd backend && npm install
RUN cd frontend && npm install

# Copier tout le code source
COPY backend/ backend/
COPY frontend/ frontend/

# Exposer le port
EXPOSE 3000

# Démarrer l'application
CMD ["sh", "-c", "cd backend && node server.js"]
