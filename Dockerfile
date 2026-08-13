FROM node:18-alpine

WORKDIR /app

# Copier et installer le backend seulement
COPY backend/package.json backend/
RUN cd backend && npm install

# Copier tout le code
COPY backend/ backend/
COPY frontend/ frontend/

# Exposer le port
EXPOSE 3000

# Démarrer l'application
CMD ["node", "backend/server.js"]
