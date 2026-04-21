FROM node:18-slim

WORKDIR /app

# Install build tools for serialport (needed for some Linux distros)
RUN apt-get update && apt-get install -y python3 make g++ gcc

COPY package*.json ./
RUN npm install

COPY . .

# Note: The serial port (e.g., /dev/ttyUSB0) will be passed at runtime via docker-compose
CMD ["node", "sms_server.js"]
