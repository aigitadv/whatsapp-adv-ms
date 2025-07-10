FROM node:20
WORKDIR /app
COPY backend/package.json backend/package-lock.json* ./
RUN npm install
COPY backend ./backend
CMD ["node", "backend/index.js"]
