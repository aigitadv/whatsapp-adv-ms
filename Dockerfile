FROM node:18-alpine
WORKDIR /app
COPY backend/package*.json ./backend/
RUN cd backend && npm install --production
COPY backend ./backend
COPY public  ./public
VOLUME [ "/data" ]
ENV DB_PATH=/data/messages.db
EXPOSE 3000
CMD ["node", "backend/index.js"]
