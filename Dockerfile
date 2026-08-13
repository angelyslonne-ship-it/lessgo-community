FROM node:18-alpine

WORKDIR /app

COPY backend/package*.json backend/
COPY frontend/package*.json frontend/

RUN cd backend && npm install
RUN cd frontend && npm install

COPY backend/ backend/
COPY frontend/ frontend/

EXPOSE 3000

CMD ["sh", "-c", "cd backend && node server.js"]
