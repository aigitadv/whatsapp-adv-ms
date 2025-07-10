FROM node:20
WORKDIR /app
COPY backend/package.json backend/package-lock.json* ./ 
RUN npm install
COPY backend ./backend
COPY public ./public
WORKDIR /app/backend
CMD ["node", "index.js"]
